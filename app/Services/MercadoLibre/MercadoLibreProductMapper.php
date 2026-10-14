<?php

namespace App\Services\MercadoLibre;

use Illuminate\Support\Str;

class MercadoLibreProductMapper
{
    private readonly float $priceFactor;

    public function __construct()
    {
        $this->priceFactor = (float) config('mercadolibre.price_factor', 1.0);
    }

    /**
     * Transforma una carga útil cruda de Mercado Libre hacia los atributos normalizados de Product.
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     attributes: array<string, mixed>,
     *     photo_urls: array<int, string>,
     *     target_price: float,
     *     target_stock: int
     * }
     */
    public function map(array $payload): array
    {
        $rawAttributes = $payload['attributes'] ?? [];
        $title = trim((string) ($payload['title'] ?? 'Refacción'));
        $externalId = (string) ($payload['id'] ?? '');

        $partNumber = $this->extractAttributeValue($rawAttributes, 'PART_NUMBER');
        $oemNumber = $this->extractAttributeValue($rawAttributes, 'OEM_NUMBER');
        $brand = $this->extractAttributeValue($rawAttributes, 'BRAND') ?? $this->detectBrandFromTitle($title);
        $model = $this->extractAttributeValue($rawAttributes, 'MODEL') ?? $this->detectModelFromTitle($title, $brand);
        $attrYear = $this->extractAttributeValue($rawAttributes, 'VEHICLE_YEAR');

        [$yearStart, $yearEnd] = $this->extractYears($title, $attrYear);

        $category = $this->mapCategory($payload['category_id'] ?? null, $title);
        $dimensions = $this->parseDimensions($payload['shipping']['dimensions'] ?? null, $category);

        $side = $this->extractAttributeValue($rawAttributes, 'SIDE');
        $position = $this->extractAttributeValue($rawAttributes, 'POSITION');

        $rawStock = (int) ($payload['available_quantity'] ?? 0);
        $meliStatus = (string) ($payload['status'] ?? 'active');
        $publishedStatus = ($meliStatus === 'active' && $rawStock > 0) ? 'published' : 'draft';

        $rawPrice = (float) ($payload['price'] ?? 0.0);
        $webPrice = round($rawPrice * $this->priceFactor, 2);

        $sku = ! empty($partNumber)
            ? strtoupper(Str::slug($partNumber))
            : 'JC-' . preg_replace('/[^A-Za-z0-9]/', '', $externalId);

        $photos = $this->extractPictures($payload['pictures'] ?? []);

        $attributes = [
            'mercadolibre_id' => $externalId,
            'mercadolibre_permalink' => $payload['permalink'] ?? null,
            'title' => $title,
            'slug' => Str::slug("{$title}-{$externalId}"),
            'sku' => $sku,
            'part_number' => $partNumber,
            'oem_number' => $oemNumber,
            'category' => $category,
            'vehicle_brand' => $brand,
            'vehicle_model' => $model,
            'year_start' => $yearStart,
            'year_end' => $yearEnd,
            'side' => $side,
            'position' => $position,
            'condition' => 'used',
            'description' => $payload['description'] ?? "{$title} compatible con {$brand} {$model} ({$yearStart}–{$yearEnd}). Garantía oficial de JC Autopartes.",
            'height_cm' => $dimensions['height_cm'],
            'width_cm' => $dimensions['width_cm'],
            'length_cm' => $dimensions['length_cm'],
            'weight_kg' => $dimensions['weight_kg'],
            'status' => $publishedStatus,
            'is_featured' => false,
            'raw_attributes' => $rawAttributes,
            'last_synced_at' => now(),
        ];

        return [
            'attributes' => $attributes,
            'photo_urls' => $photos,
            'target_price' => $webPrice,
            'target_stock' => $rawStock,
        ];
    }

    /**
     * Extrae el año inicial y final mediante expresiones regulares sobre el título y atributos.
     *
     * @return array{0: int, 1: int}
     */
    public function extractYears(string $title, ?string $attributeYear): array
    {
        $currentYear = (int) date('Y');

        // 1. Rango en título: ej. "2015-2019", "2016 a 2022", "15-19"
        if (preg_match('/(20\d{2}|19\d{2})\s*(?:-|a|\/)\s*(20\d{2}|19\d{2})/i', $title, $matches)) {
            $y1 = (int) $matches[1];
            $y2 = (int) $matches[2];

            return [min($y1, $y2), max($y1, $y2)];
        }

        // 2. Año único en título
        if (preg_match('/(20\d{2}|19\d{2})/', $title, $matches)) {
            $year = (int) $matches[1];

            return [$year, $year];
        }

        // 3. Atributo VEHICLE_YEAR
        if (! empty($attributeYear) && is_numeric($attributeYear)) {
            $year = (int) $attributeYear;

            return [$year, $year];
        }

        return [2015, $currentYear];
    }

    /**
     * Mapea el identificador de categoría de Mercado Libre a las 7 categorías de JC Autopartes.
     */
    public function mapCategory(?string $categoryId, string $title): string
    {
        $lowerTitle = mb_strtolower($title);

        if ($categoryId === 'MLM187514' || str_contains($lowerTitle, 'motor')) {
            return 'Motores';
        }

        if ($categoryId === 'MLM187515' || str_contains($lowerTitle, 'transmision') || str_contains($lowerTitle, 'transmisión') || str_contains($lowerTitle, 'caja')) {
            return 'Transmisiones';
        }

        if (str_contains($lowerTitle, 'cabeza') || str_contains($lowerTitle, 'cigueñal') || str_contains($lowerTitle, 'cigüeñal') || str_contains($lowerTitle, 'arbol') || str_contains($lowerTitle, 'árbol')) {
            return 'Partes Internas';
        }

        if ($categoryId === 'MLM187516' || str_contains($lowerTitle, 'faro') || str_contains($lowerTitle, 'calavera') || str_contains($lowerTitle, 'puerta') || str_contains($lowerTitle, 'cofre') || str_contains($lowerTitle, 'fascia')) {
            return 'Colisión y Carrocería';
        }

        if ($categoryId === 'MLM187519' || str_contains($lowerTitle, 'direccion') || str_contains($lowerTitle, 'dirección') || str_contains($lowerTitle, 'suspension') || str_contains($lowerTitle, 'suspensión') || str_contains($lowerTitle, 'cremallera') || str_contains($lowerTitle, 'horquilla')) {
            return 'Suspensión y Dirección';
        }

        if ($categoryId === 'MLM187517' || str_contains($lowerTitle, 'alternador') || str_contains($lowerTitle, 'marcha') || str_contains($lowerTitle, 'sensor') || str_contains($lowerTitle, 'computadora')) {
            return 'Eléctrico y Sensores';
        }

        if ($categoryId === 'MLM187518' || str_contains($lowerTitle, 'compresor') || str_contains($lowerTitle, 'radiador') || str_contains($lowerTitle, 'clima') || str_contains($lowerTitle, 'aire')) {
            return 'Enfriamiento';
        }

        return 'Colisión y Carrocería';
    }

    /**
     * Desglosa la cadena de envío "{alto}x{ancho}x{largo},{peso_gramos}" en medidas y peso en kg.
     *
     * @return array{height_cm: float, width_cm: float, length_cm: float, weight_kg: float}
     */
    public function parseDimensions(?string $dimensionsString, string $category): array
    {
        $defaults = match ($category) {
            'Motores' => ['height_cm' => 65.0, 'width_cm' => 55.0, 'length_cm' => 70.0, 'weight_kg' => 85.0],
            'Transmisiones' => ['height_cm' => 45.0, 'width_cm' => 45.0, 'length_cm' => 80.0, 'weight_kg' => 60.0],
            'Partes Internas' => ['height_cm' => 20.0, 'width_cm' => 25.0, 'length_cm' => 50.0, 'weight_kg' => 18.0],
            'Suspensión y Dirección' => ['height_cm' => 20.0, 'width_cm' => 30.0, 'length_cm' => 100.0, 'weight_kg' => 12.0],
            'Eléctrico y Sensores' => ['height_cm' => 20.0, 'width_cm' => 20.0, 'length_cm' => 25.0, 'weight_kg' => 5.0],
            default => ['height_cm' => 25.0, 'width_cm' => 30.0, 'length_cm' => 60.0, 'weight_kg' => 6.0],
        };

        if (empty($dimensionsString) || ! str_contains($dimensionsString, ',')) {
            return $defaults;
        }

        [$linearPart, $weightPart] = explode(',', $dimensionsString, 2);
        $dims = explode('x', $linearPart);

        if (count($dims) === 3 && is_numeric($dims[0]) && is_numeric($dims[1]) && is_numeric($dims[2])) {
            $height = (float) $dims[0];
            $width = (float) $dims[1];
            $length = (float) $dims[2];
        } else {
            $height = $defaults['height_cm'];
            $width = $defaults['width_cm'];
            $length = $defaults['length_cm'];
        }

        $weightGrams = is_numeric($weightPart) ? (float) $weightPart : 0.0;
        $weightKg = $weightGrams > 0 ? round($weightGrams / 1000, 2) : $defaults['weight_kg'];

        return [
            'height_cm' => max(1.0, $height),
            'width_cm' => max(1.0, $width),
            'length_cm' => max(1.0, $length),
            'weight_kg' => max(0.1, $weightKg),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $pictures
     * @return array<int, string>
     */
    private function extractPictures(array $pictures): array
    {
        return collect($pictures)
            ->map(fn ($pic) => $pic['secure_url'] ?? $pic['url'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function extractAttributeValue(array $attributes, string $attributeId): ?string
    {
        foreach ($attributes as $attr) {
            if (($attr['id'] ?? null) === $attributeId) {
                return ! empty($attr['value_name']) ? trim((string) $attr['value_name']) : null;
            }
        }

        return null;
    }

    private function detectBrandFromTitle(string $title): string
    {
        $commonBrands = ['Nissan', 'Chevrolet', 'Volkswagen', 'Toyota', 'Ford', 'Honda', 'Mazda', 'Kia', 'Hyundai', 'Dodge', 'Jeep'];

        foreach ($commonBrands as $brand) {
            if (stripos($title, $brand) !== false) {
                return $brand;
            }
        }

        return 'Universal';
    }

    private function detectModelFromTitle(string $title, string $brand): string
    {
        $commonModels = [
            'Versa', 'NP300', 'Frontier', 'Sentra', 'Kicks', 'March', 'Urvan',
            'Silverado', 'Aveo', 'Sonic', 'Cruze', 'Beat', 'Spark',
            'Jetta', 'Golf', 'Vento', 'Polo',
            'Hilux', 'Corolla', 'RAV4', 'Yaris',
            'Figo', 'F-150', 'Lobo', 'Ranger', 'Focus',
        ];

        foreach ($commonModels as $model) {
            if (stripos($title, $model) !== false) {
                return $model;
            }
        }

        return 'Varios';
    }
}
