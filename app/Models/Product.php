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
        'mercadolibre_id',
        'mercadolibre_permalink',
        'title',
        'slug',
        'sku',
        'part_number',
        'oem_number',
        'category',
        'vehicle_brand',
        'vehicle_model',
        'year_start',
        'year_end',
        'side',
        'position',
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
        'raw_attributes',
        'last_synced_at',
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
            'raw_attributes' => 'array',
            'last_synced_at' => 'datetime',
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
        if ($this->relationLoaded('primaryPhoto')) {
            $photo = $this->primaryPhoto;
        } elseif ($this->relationLoaded('photos')) {
            $photo = $this->photos->first();
        } else {
            $photo = $this->primaryPhoto()->first();
        }

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

    public function isMercadoLibreSynced(): bool
    {
        return ! empty($this->mercadolibre_id);
    }

    public function getMeliSpec(string $attributeId): ?string
    {
        $attributes = $this->raw_attributes ?? [];
        if (! is_array($attributes)) {
            return null;
        }

        foreach ($attributes as $attr) {
            if (($attr['id'] ?? null) === $attributeId) {
                return $attr['value_name'] ?? null;
            }
        }

        return null;
    }

    public function sideAndPositionLabel(): ?string
    {
        $parts = array_filter([$this->position, $this->side]);

        return ! empty($parts) ? implode(' • ', $parts) : null;
    }
}
