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

    public function deliveryMethodLabel(): string
    {
        return match ($this->delivery_method) {
            self::DELIVERY_PICKUP => 'Recolección en Mostrador (Tepatitlán)',
            default => 'Envío a Domicilio (Paquetería)',
        };
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

        $items = ($this->relationLoaded('items') ? $this->items : collect())->map(fn (OrderItem $item) => [
            'product_id' => $item->product_id ?? $item->id,
            'title' => $item->product_title,
            'sku' => $item->product_sku,
            'photo_url' => $item->photo_url,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
        ])->all();

        return [
            'order_number' => $this->order_number,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'shipping_cost' => $this->shipping_cost,
            'items' => $items,
            'back_urls' => [
                'success' => route('checkout.success'),
                'failure' => route('checkout.failure'),
                'pending' => route('checkout.pending'),
            ],
        ];
    }
}

