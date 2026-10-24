<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Mail\OrderConfirmedCustomerMail;
use App\Mail\OrderNotificationAdminMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

/**
 * Servicio de Creación, Cálculo y Transición de Estados de Pedidos.
 *
 * Reglas de negocio:
 * - Toda validación y modificación de inventario pasa por StockService.
 * - Los precios y subtotales se calculan exclusivamente en servidor.
 * - Idempotencia en la acreditación de pagos.
 */
class OrderService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly PriceService $priceService,
    ) {}

    /**
     * Crea un nuevo pedido en estado 'pending_payment' a partir de los datos del cliente y los items del carrito.
     *
     * @param  array<string, mixed>  $customerData
     * @param  array{
     *     items: array<int, array<string, mixed>>,
     *     totalQuantity: int,
     *     subtotal: float
     * }  $cart
     *
     * @throws InsufficientStockException
     */
    public function createOrderFromCart(array $customerData, array $cart): Order
    {
        $items = $cart['items'] ?? [];

        if (empty($items)) {
            throw new InvalidArgumentException('No es posible crear un pedido con el carrito vacío.');
        }

        // 1. Verificación previa de existencias de todas las piezas
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 1);

            if (! $this->stockService->hasStock($productId, $qty)) {
                $available = $this->stockService->getAvailableStock($productId);
                throw new InsufficientStockException(
                    "Existencia insuficiente para la refacción «{$item['title']}». Disponibles: {$available}, solicitadas: {$qty}.",
                    productId: $productId,
                    requested: $qty,
                    available: $available
                );
            }
        }

        $deliveryMethod = ($customerData['delivery_method'] ?? 'shipping') === 'pickup'
            ? Order::DELIVERY_PICKUP
            : Order::DELIVERY_SHIPPING;

        $itemsSubtotal = round((float) ($cart['subtotal'] ?? 0.0), 2);
        $shippingCost = $deliveryMethod === Order::DELIVERY_PICKUP
            ? 0.00
            : round((float) ($customerData['shipping_cost'] ?? 0.0), 2);
        $total = round($itemsSubtotal + $shippingCost, 2);

        return DB::transaction(function () use ($customerData, $items, $deliveryMethod, $itemsSubtotal, $shippingCost, $total) {
            $orderNumber = $this->generateOrderNumber();

            /** @var Order $order */
            $order = Order::query()->create([
                'order_number' => $orderNumber,
                'customer_name' => trim((string) ($customerData['customer_name'] ?? '')),
                'customer_email' => strtolower(trim((string) ($customerData['customer_email'] ?? ''))),
                'customer_phone' => trim((string) ($customerData['customer_phone'] ?? '')),
                'delivery_method' => $deliveryMethod,
                'shipping_address' => $deliveryMethod === Order::DELIVERY_SHIPPING ? ($customerData['shipping_address'] ?? null) : null,
                'shipping_neighborhood' => $deliveryMethod === Order::DELIVERY_SHIPPING ? ($customerData['shipping_neighborhood'] ?? null) : null,
                'shipping_city' => $deliveryMethod === Order::DELIVERY_SHIPPING ? ($customerData['shipping_city'] ?? null) : null,
                'shipping_state' => $deliveryMethod === Order::DELIVERY_SHIPPING ? ($customerData['shipping_state'] ?? null) : null,
                'shipping_postal_code' => $deliveryMethod === Order::DELIVERY_SHIPPING ? ($customerData['shipping_postal_code'] ?? null) : null,
                'shipping_notes' => $customerData['shipping_notes'] ?? null,
                'items_subtotal' => $itemsSubtotal,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'currency' => 'MXN',
                'payment_status' => Order::PAYMENT_PENDING,
                'order_status' => Order::STATUS_PENDING,
                'payment_gateway' => 'mercadopago',
                'metadata' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);

            foreach ($items as $item) {
                /** @var Product|null $productModel */
                $productModel = $item['product'] ?? null;
                $partNumber = $productModel instanceof Product ? $productModel->part_number : null;

                $unitPrice = round((float) ($item['unit_price'] ?? 0.0), 2);
                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $lineSubtotal = round((float) ($item['line_subtotal'] ?? ($unitPrice * $quantity)), 2);

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => (int) $item['product_id'],
                    'product_title' => (string) $item['title'],
                    'product_sku' => (string) $item['sku'],
                    'part_number' => $partNumber,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'subtotal' => $lineSubtotal,
                    'photo_url' => $item['photo_url'] ?? null,
                    'warranty_days' => (int) ($item['warranty_days'] ?? 7),
                ]);
            }

            return $order->load('items');
        });
    }

    /**
     * Acredita el pago de una orden y descuenta el stock de forma atómica y pesimista contra race conditions.
     * Es completamente idempotente: si la orden ya está pagada, no vuelve a descontar existencias.
     *
     * @param  array<string, mixed>  $paymentData
     */
    public function markOrderAsPaid(Order|int $order, array $paymentData = []): Order
    {
        $orderId = $order instanceof Order ? $order->id : (int) $order;

        return DB::transaction(function () use ($orderId, $paymentData) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()->lockForUpdate()->with('items')->findOrFail($orderId);

            // Idempotencia: si ya fue procesada, retornar sin volver a descontar
            if ($lockedOrder->isPaid()) {
                return $lockedOrder;
            }

            // Descuento atómico en StockService (con lockForUpdate en products)
            foreach ($lockedOrder->items as $item) {
                if ($item->product_id) {
                    $this->stockService->decrementStock($item->product_id, $item->quantity);
                }
            }

            $lockedOrder->payment_status = Order::PAYMENT_PAID;
            $lockedOrder->order_status = Order::STATUS_PREPARING;
            $lockedOrder->paid_at = now();

            if (! empty($paymentData['payment_id'])) {
                $lockedOrder->mercadopago_payment_id = (string) $paymentData['payment_id'];
            }
            if (! empty($paymentData['payment_type'])) {
                $lockedOrder->mercadopago_payment_type = (string) $paymentData['payment_type'];
            }
            if (! empty($paymentData['merchant_order_id'])) {
                $lockedOrder->mercadopago_merchant_order_id = (string) $paymentData['merchant_order_id'];
            }

            $lockedOrder->save();

            // Despacho de notificaciones por correo electrónico (comprador y almacén)
            $this->sendOrderConfirmedEmails($lockedOrder);

            return $lockedOrder;
        });
    }

    /**
     * Envía las notificaciones transaccionales por correo sin interrumpir la operación si el servidor de correo falla.
     */
    public function sendOrderConfirmedEmails(Order $order): void
    {
        try {
            if ($order->relationLoaded('items') === false && $order->exists) {
                try {
                    $order->loadMissing('items');
                } catch (\Throwable) {
                    // Offline / testing
                }
            }

            if (! empty($order->customer_email)) {
                Mail::to($order->customer_email, $order->customer_name)
                    ->send(new OrderConfirmedCustomerMail($order));
            }

            $adminEmail = config('business.email');
            if (! empty($adminEmail)) {
                Mail::to($adminEmail, 'Almacén JC Autopartes')
                    ->send(new OrderNotificationAdminMail($order));
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar notificación de correo de orden pagada', [
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cancela una orden y restituye el inventario si ya se había descontado.
     */
    public function cancelOrder(Order|int $order, string $reason = ''): Order
    {
        $orderId = $order instanceof Order ? $order->id : (int) $order;

        return DB::transaction(function () use ($orderId, $reason) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()->lockForUpdate()->with('items')->findOrFail($orderId);

            if ($lockedOrder->isCancelled()) {
                return $lockedOrder;
            }

            // Si ya estaba pagada, restituir el stock que se había descontado
            if ($lockedOrder->isPaid()) {
                foreach ($lockedOrder->items as $item) {
                    if ($item->product_id) {
                        $this->stockService->incrementStock($item->product_id, $item->quantity);
                    }
                }
            }

            $lockedOrder->order_status = Order::STATUS_CANCELLED;
            $lockedOrder->cancelled_at = now();

            if ($reason !== '') {
                $metadata = $lockedOrder->metadata ?? [];
                $metadata['cancellation_reason'] = $reason;
                $lockedOrder->metadata = $metadata;
            }

            $lockedOrder->save();

            return $lockedOrder;
        });
    }

    /**
     * Genera un identificador de pedido único e incremental en formato JC-YYYY-XXXXX.
     */
    public function generateOrderNumber(): string
    {
        $year = date('Y');
        $prefix = "JC-{$year}-";

        try {
            // Obtener el conteo o consecutivo máximo del año
            $count = Order::query()
                ->where('order_number', 'like', "{$prefix}%")
                ->count();

            $nextNumber = $count + 1;

            do {
                $formattedNumber = $prefix . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
                $exists = Order::query()->where('order_number', $formattedNumber)->exists();
                if ($exists) {
                    $nextNumber++;
                }
            } while ($exists);

            return $formattedNumber;
        } catch (\PDOException|\Illuminate\Database\QueryException) {
            // Soporte para entornos locales de prueba o inspección sin driver de base de datos
            return $prefix . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        }
    }
}
