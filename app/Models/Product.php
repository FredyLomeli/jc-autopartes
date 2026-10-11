<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modelo Product (Artículo de catálogo).
 *
 * NOTA IMPORTANTE (FASE A0 -> FASE B):
 * ------------------------------------
 * Los campos de este modelo son PROVISIONALES para el andamiaje visual de la Fase A0.
 * En la Fase B se reemplazarán por los campos reales provenientes de Mercado Libre.
 * La lectura y modificación de `price` y `stock` debe hacerse SIEMPRE a través de
 * `App\Services\PriceService` y `App\Services\StockService`.
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'sku',
        'category',
        'vehicle_brand',
        'vehicle_model',
        'year_start',
        'year_end',
        'condition',
        'price',
        'stock',
        'description',
        'weight_kg',
        'height_cm',
        'width_cm',
        'length_cm',
        'status',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'year_start' => 'integer',
            'year_end' => 'integer',
            'price' => 'decimal:2',
            'stock' => 'integer',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'width_cm' => 'decimal:2',
            'length_cm' => 'decimal:2',
            'is_featured' => 'boolean',
        ];
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProductPhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(ProductPhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function getPrimaryPhotoUrlAttribute(): string
    {
        $photo = $this->relationLoaded('photos')
            ? $this->photos->first()
            : $this->primaryPhoto()->first();

        if ($photo) {
            return $photo->url;
        }

        return asset('imagenes/placeholders/colision.svg');
    }

    /**
     * Plazo de garantía oficial según la categoría del artículo (docs/brief_diseno.md).
     */
    public function warrantyDays(): int
    {
        return match ($this->category) {
            'Motores', 'Transmisiones' => 30,
            'Partes Internas' => 15,
            default => 7,
        };
    }

    public function conditionDescription(): string
    {
        return match ($this->condition) {
            'A' => 'Grado A — Excelente estado estético y funcional',
            'B' => 'Grado B — Buen estado funcional con detalles menores de uso',
            'C' => 'Grado C — Funcional para reacondicionar o despiece',
            default => 'Pieza usada original verificada',
        };
    }
}
