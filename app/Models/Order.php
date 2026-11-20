<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const PAYMENT_PENDING = 'pending_payment';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_REFUNDED = 'refunded';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public const DELIVERY_SHIPPING = 'shipping';
    public const DELIVERY_PICKUP = 'pickup';

    protected $fillable = [
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'delivery_method',
        'shipping_address',
        'shipping_neighborhood',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_notes',
        'items_subtotal',
        'shipping_cost',
        'total',
        'currency',
        'payment_status',
        'order_status',
        'payment_gateway',
        'mercadopago_preference_id',
        'mercadopago_payment_id',
        'mercadopago_payment_type',
        'mercadopago_merchant_order_id',
        'paid_at',
        'cancelled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'items_subtotal' => 'float',
            'shipping_cost' => 'float',
            'total' => 'float',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function isPendingPayment(): bool
    {
        return $this->payment_status === self::PAYMENT_PENDING;
    }

    public function isCancelled(): bool
    {
        return $this->order_status === self::STATUS_CANCELLED;
    }

    public function isShipping(): bool
    {
        return $this->delivery_method === self::DELIVERY_SHIPPING;
    }

    public function isPickup(): bool
    {
        return $this->delivery_method === self::DELIVERY_PICKUP;
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_PAID => 'Pagado',
            self::PAYMENT_FAILED => 'Pago Fallido / Rechazado',
            self::PAYMENT_REFUNDED => 'Reembolsado',
            default => 'Pendiente de Pago',
        };
    }

    public function orderStatusLabel(): string
    {
        return match ($this->order_status) {
            self::STATUS_PREPARING => 'En Preparación / Almacén',
            self::STATUS_SHIPPED => 'Enviado con Guía',
            self::STATUS_DELIVERED => 'Entregado al Cliente',
            self::STATUS_CANCELLED => 'Cancelado',
            default => 'Pendiente',
        };
    }

    public function discountAmount(): float
    {
        return round(max(0.0, (float) ($this->metadata['discount_amount'] ?? 0.0)), 2);
    }

    public function couponCode(): ?string
    {
        $code = $this->metadata['coupon']['code'] ?? null;

        return is_string($code) && $code !== '' ? $code : null;
    }

    public function deliveryVariant(): string
    {
        $variant = $this->metadata['delivery_variant'] ?? null;

        if (is_string($variant) && in_array($variant, ['domicilio', 'ocurre', 'pickup'], true)) {
            return $variant;
        }

        return $this->delivery_method === self::DELIVERY_PICKUP ? 'pickup' : 'domicilio';
    }

    public function deliveryMethodLabel(): string
    {
        if ($this->delivery_method === self::DELIVERY_PICKUP) {
            return 'Recolección en Mostrador (Tepatitlán)';
        }

        return $this->deliveryVariant() === 'ocurre'
            ? 'Servicio Ocurre (Paquetería)'
            : 'Envío a Domicilio (Paquetería)';
    }

    /**
     * @return array<string, mixed>
     */
    public function toPaymentPayload(): array
    {
        if (! $this->relationLoaded('items') && $this->exists) {
            try {
                $this->loadMissing('items');
            } catch (\Throwable) {
                // Modo offline / testing
            }
        }

        return [
            'order_number' => $this->order_number,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'shipping_cost' => $this->shipping_cost,
            'items' => $this->buildPaymentItems(),
            'back_urls' => [
                'success' => route('checkout.success'),
                'failure' => route('checkout.failure'),
                'pending' => route('checkout.pending'),
            ],
        ];
    }

    /**
     * Prorratea cualquier descuento de cupón entre los ítems enviados a Mercado Pago
     * para asegurar que el cobro en Checkout Pro coincida al centavo con el total de la orden.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildPaymentItems(): array
    {
        $orderItems = $this->relationLoaded('items') ? $this->items : collect();

        if ($orderItems->isEmpty()) {
            return [];
        }

        $discount = $this->discountAmount();

        if ($discount <= 0.0) {
            return $orderItems->map(fn (OrderItem $item) => [
                'product_id' => $item->product_id ?? $item->id,
                'title' => $item->product_title,
                'sku' => $item->product_sku,
                'photo_url' => $item->photo_url,
                'quantity' => max(1, (int) $item->quantity),
                'unit_price' => round((float) $item->unit_price, 2),
            ])->all();
        }

        $lineItems = $orderItems->values()->map(function (OrderItem $item): array {
            $qty = max(1, (int) $item->quantity);
            $origCents = max(0, (int) round(((float) $item->unit_price) * $qty * 100));

            return [
                'item' => $item,
                'qty' => $qty,
                'orig_cents' => $origCents,
            ];
        })->all();

        $totalOrigCents = array_sum(array_column($lineItems, 'orig_cents'));
        $subtotalCents = $this->items_subtotal > 0
            ? (int) round(((float) $this->items_subtotal) * 100)
            : $totalOrigCents;
        $discountCents = (int) round($discount * 100);
        $targetCents = max(0, $subtotalCents - $discountCents);

        if ($totalOrigCents <= 0 || $targetCents <= 0) {
            return [];
        }

        $allocated = [];
        $remainingTarget = $targetCents;
        $remainingOrig = $totalOrigCents;
        $lastIdx = count($lineItems) - 1;

        foreach ($lineItems as $idx => $entry) {
            if ($idx === $lastIdx || $remainingOrig <= 0) {
                $allocated[$idx] = max(0, $remainingTarget);
            } else {
                $share = (int) round($remainingTarget * ($entry['orig_cents'] / $remainingOrig));
                $share = max(0, min($remainingTarget, $share));
                $allocated[$idx] = $share;
                $remainingTarget -= $share;
                $remainingOrig -= $entry['orig_cents'];
            }
        }

        $paymentItems = [];
        foreach ($lineItems as $idx => $entry) {
            /** @var OrderItem $item */
            $item = $entry['item'];
            $qty = $entry['qty'];
            $lineCents = $allocated[$idx];

            $baseUnitCents = intdiv($lineCents, $qty);
            $remCents = $lineCents % $qty;

            if ($remCents === 0) {
                $paymentItems[] = [
                    'product_id' => $item->product_id ?? $item->id,
                    'title' => $item->product_title,
                    'sku' => $item->product_sku,
                    'photo_url' => $item->photo_url,
                    'quantity' => $qty,
                    'unit_price' => round($baseUnitCents / 100, 2),
                ];
            } else {
                $paymentItems[] = [
                    'product_id' => $item->product_id ?? $item->id,
                    'title' => $item->product_title,
                    'sku' => $item->product_sku,
                    'photo_url' => $item->photo_url,
                    'quantity' => $qty - $remCents,
                    'unit_price' => round($baseUnitCents / 100, 2),
                ];
                $paymentItems[] = [
                    'product_id' => $item->product_id ?? $item->id,
                    'title' => $item->product_title,
                    'sku' => $item->product_sku,
                    'photo_url' => $item->photo_url,
                    'quantity' => $remCents,
                    'unit_price' => round(($baseUnitCents + 1) / 100, 2),
                ];
            }
        }

        return $paymentItems;
    }
}

