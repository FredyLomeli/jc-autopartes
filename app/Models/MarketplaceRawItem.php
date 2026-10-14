<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceRawItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace',
        'external_id',
        'title',
        'status',
        'raw_payload',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'fetched_at' => 'datetime',
        ];
    }

    /**
     * Obtiene el valor de un atributo específico dentro del payload crudo de Mercado Libre.
     */
    public function getMeliAttribute(string $attributeId): ?string
    {
        $attributes = $this->raw_payload['attributes'] ?? [];

        foreach ($attributes as $attr) {
            if (($attr['id'] ?? null) === $attributeId) {
                return $attr['value_name'] ?? null;
            }
        }

        return null;
    }

    /**
     * Obtiene la lista de URLs de fotografías de la publicación.
     *
     * @return array<int, string>
     */
    public function getPictureUrls(): array
    {
        $pictures = $this->raw_payload['pictures'] ?? [];

        return collect($pictures)
            ->map(fn ($pic) => $pic['secure_url'] ?? $pic['url'] ?? null)
            ->filter()
            ->values()
            ->all();
    }
}
