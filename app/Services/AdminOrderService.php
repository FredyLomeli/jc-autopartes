<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminOrderService
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly PriceService $priceService,
    ) {}

    /**
     * Obtiene el listado paginado y filtrado de pedidos para el panel administrativo.
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     data: array<int, array<string, mixed>>,
     *     links: array<int, mixed>,
     *     current_page: int,
     *     last_page: int,
     *     per_page: int,
     *     total: int,
     *     from: ?int,
     *     to: ?int
     * }
     */
    public function getPaginatedOrders(array $filters = [], int $perPage = 15): array
    {
        try {
            $query = Order::query()
                ->with(['items.product'])
                ->orderByDesc('id');

            // 1. Filtro de búsqueda libre (Folio, Cliente, Email, Teléfono, ID Mercado Pago)
            $search = trim((string) ($filters['q'] ?? ''));
            if ($search !== '') {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('mercadopago_payment_id', 'like', "%{$search}%");
                });
            }

            // 2. Filtro de estado de pago
            $paymentStatus = trim((string) ($filters['payment_status'] ?? ''));
            if ($paymentStatus !== '' && $paymentStatus !== 'all') {
                $query->where('payment_status', $paymentStatus);
            }

            // 3. Filtro de estado logístico
            $orderStatus = trim((string) ($filters['order_status'] ?? ''));
            if ($orderStatus !== '' && $orderStatus !== 'all') {
                $query->where('order_status', $orderStatus);
            }

            // 4. Filtro de método de entrega
            $deliveryMethod = trim((string) ($filters['delivery_method'] ?? ''));
            if ($deliveryMethod !== '' && $deliveryMethod !== 'all') {
                $query->where('delivery_method', $deliveryMethod);
            }

            /** @var LengthAwarePaginator $paginator */
            $paginator = $query->paginate($perPage)->withQueryString();

            $transformedData = collect($paginator->items())->map(function (Order $order) {
                return $this->formatOrderListItem($order);
            })->all();

            return [
                'data' => $transformedData,
                'links' => $paginator->linkCollection()->toArray(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ];
        } catch (Throwable $e) {
            Log::warning('No se pudo consultar orders en DB (modo testing/offline): ' . $e->getMessage());

            return [
                'data' => [],
                'links' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
                'from' => null,
                'to' => null,
            ];
        }
    }

    /**
     * Formatea un pedido para la tabla de administración.
     *
     * @return array<string, mixed>
     */
    public function formatOrderListItem(Order $order): array
    {
        $itemsCount = $order->relationLoaded('items') ? $order->items->sum('quantity') : 0;
        $firstItemTitle = $order->relationLoaded('items') && $order->items->isNotEmpty()
            ? $order->items->first()->product_title
            : 'Sin partidas';

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'delivery_method' => $order->delivery_method,
            'delivery_method_label' => $order->deliveryMethodLabel(),
            'items_count' => $itemsCount,
            'summary_item' => $firstItemTitle . ($itemsCount > 1 ? " (+".($itemsCount - 1)." más)" : ''),
            'total' => $order->total,
            'formatted_total' => $this->priceService->format($order->total),
            'payment_status' => $order->payment_status,
            'payment_status_label' => $order->paymentStatusLabel(),
            'order_status' => $order->order_status,
            'order_status_label' => $order->orderStatusLabel(),
            'paid_at_formatted' => $order->paid_at ? $order->paid_at->format('d/m/Y H:i') : null,
            'created_at_formatted' => $order->created_at ? $order->created_at->format('d/m/Y H:i') : null,
        ];
    }

    /**
     * Obtiene los detalles completos de una orden para la pantalla de detalle.
     *
     * @return array<string, mixed>
     */
    public function getOrderDetails(Order $order): array
    {
        if ($order->exists && ! $order->relationLoaded('items')) {
            try {
                $order->loadMissing(['items.product.primaryPhoto']);
            } catch (Throwable) {
                // Offline / testing
            }
        }

        $items = $order->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_title' => $item->product_title,
                'product_sku' => $item->product_sku,
                'part_number' => $item->part_number,
                'unit_price' => $item->unit_price,
                'formatted_unit_price' => $this->priceService->format($item->unit_price),
                'quantity' => $item->quantity,
                'subtotal' => $item->subtotal,
                'formatted_subtotal' => $this->priceService->format($item->subtotal),
                'photo_url' => $item->photo_url,
                'warranty_days' => $item->warranty_days,
            ];
        })->all();

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'delivery_method' => $order->delivery_method,
            'delivery_method_label' => $order->deliveryMethodLabel(),
            'shipping_address' => $order->shipping_address,
            'shipping_neighborhood' => $order->shipping_neighborhood,
            'shipping_city' => $order->shipping_city,
            'shipping_state' => $order->shipping_state,
            'shipping_postal_code' => $order->shipping_postal_code,
            'shipping_notes' => $order->shipping_notes,
            'items_subtotal' => $order->items_subtotal,
            'formatted_subtotal' => $this->priceService->format($order->items_subtotal),
            'shipping_cost' => $order->shipping_cost,
            'formatted_shipping_cost' => $this->priceService->format($order->shipping_cost),
            'total' => $order->total,
            'formatted_total' => $this->priceService->format($order->total),
            'currency' => $order->currency ?? 'MXN',
            'payment_status' => $order->payment_status,
            'payment_status_label' => $order->paymentStatusLabel(),
            'order_status' => $order->order_status,
            'order_status_label' => $order->orderStatusLabel(),
            'payment_gateway' => $order->payment_gateway,
            'mercadopago_preference_id' => $order->mercadopago_preference_id,
            'mercadopago_payment_id' => $order->mercadopago_payment_id,
            'mercadopago_payment_type' => $order->mercadopago_payment_type,
            'paid_at_formatted' => $order->paid_at ? $order->paid_at->format('d/m/Y H:i') : null,
            'cancelled_at_formatted' => $order->cancelled_at ? $order->cancelled_at->format('d/m/Y H:i') : null,
            'created_at_formatted' => $order->created_at ? $order->created_at->format('d/m/Y H:i') : null,
            'items' => $items,
        ];
    }

    /**
     * Actualiza el estado logístico de un pedido.
     */
    public function updateOrderStatus(Order $order, string $newStatus, ?string $notes = null): Order
    {
        $validStatuses = [
            Order::STATUS_PENDING,
            Order::STATUS_PREPARING,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
        ];

        if (! in_array($newStatus, $validStatuses, true)) {
            throw new \InvalidArgumentException("Estado de pedido «{$newStatus}» no válido.");
        }

        // Si se cancela la orden, delegamos a OrderService para que restituya el stock si ya estaba pagada
        if ($newStatus === Order::STATUS_CANCELLED) {
            return $this->orderService->cancelOrder($order, $notes ?? 'Cancelado desde panel de administración');
        }

        $order->order_status = $newStatus;

        if ($notes !== null) {
            $metadata = $order->metadata ?? [];
            $metadata['admin_notes'] = $notes;
            $metadata['status_updated_at'] = now()->toIso8601String();
            $order->metadata = $metadata;
        }

        $order->save();

        return $order->fresh();
    }

    /**
     * Calcula métricas globales de órdenes para el panel.
     *
     * @return array{
     *     total_orders: int,
     *     paid_orders: int,
     *     preparing_orders: int,
     *     shipped_orders: int,
     *     total_revenue: float,
     *     formatted_total_revenue: string
     * }
     */
    public function getOrderStats(): array
    {
        try {
            $paidQuery = Order::query()->where('payment_status', Order::PAYMENT_PAID);

            $totalOrders = Order::query()->count();
            $paidOrders = (clone $paidQuery)->count();
            $totalRevenue = (float) (clone $paidQuery)->sum('total');
            $preparingOrders = Order::query()->where('order_status', Order::STATUS_PREPARING)->count();
            $shippedOrders = Order::query()->where('order_status', Order::STATUS_SHIPPED)->count();

            return [
                'total_orders' => $totalOrders,
                'paid_orders' => $paidOrders,
                'preparing_orders' => $preparingOrders,
                'shipped_orders' => $shippedOrders,
                'total_revenue' => $totalRevenue,
                'formatted_total_revenue' => $this->priceService->format($totalRevenue),
            ];
        } catch (Throwable $e) {
            Log::warning('No se pudo calcular order stats en DB: ' . $e->getMessage());

            return [
                'total_orders' => 0,
                'paid_orders' => 0,
                'preparing_orders' => 0,
                'shipped_orders' => 0,
                'total_revenue' => 0.0,
                'formatted_total_revenue' => $this->priceService->format(0.0),
            ];
        }
    }
}
