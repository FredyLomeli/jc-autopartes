<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'badge_label',
        'mode',
        'visible_discount_percentage',
        'real_discount_percentage',
        'scope_type',
        'scope_value',
        'product_ids',
        'starts_at',
        'ends_at',
        'is_active',
        'order',
    ];

    protected $casts = [
        'visible_discount_percentage' => 'float',
        'real_discount_percentage' => 'float',
        'product_ids' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Evalúa si la campaña está vigente por fecha y estado activo.
     */
    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Evalúa si un producto califica para esta promoción.
     */
    public function appliesToProduct(Product $product): bool
    {
        if (! $this->isCurrentlyActive()) {
            return false;
        }

        return match ($this->scope_type) {
            'all' => true,
            'category' => strcasecmp((string) $product->category, (string) $this->scope_value) === 0,
            'brand' => strcasecmp((string) $product->vehicle_brand, (string) $this->scope_value) === 0,
            'custom' => is_array($this->product_ids) && in_array($product->id, $this->product_ids),
            default => false,
        };
    }
}
