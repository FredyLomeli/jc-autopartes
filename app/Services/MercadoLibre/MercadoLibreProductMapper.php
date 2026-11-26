<?php

namespace App\Services\MercadoLibre;

use Illuminate\Support\Str;

class MercadoLibreProductMapper
{
    private readonly float $priceFactor;

    /**
     * Marcas automotrices reconocidas con sus posibles alias en títulos de publicaciones.
     *
     * @var array<string, array<int, string>>
     */
    private const BRAND_ALIASES = [
        'Alfa Romeo' => ['alfa romeo'],
        'Mercedes-Benz' => ['mercedes-benz', 'mercedes benz', 'mercedes', 'm. benz'],
        'Volkswagen' => ['volkswagen', 'vw'],
        'Chevrolet' => ['chevrolet', 'chev', 'gm', 'chevy'],
        'Nissan' => ['nissan'],
        'Ford' => ['ford'],
        'Toyota' => ['toyota'],
        'Honda' => ['honda'],
        'Mazda' => ['mazda'],
        'Hyundai' => ['hyundai'],
        'Kia' => ['kia'],
        'Dodge' => ['dodge'],
        'Jeep' => ['jeep'],
        'Chrysler' => ['chrysler'],
        'Ram' => ['ram'],
        'BMW' => ['bmw'],
        'Audi' => ['audi'],
        'Volvo' => ['volvo'],
        'Renault' => ['renault'],
        'Peugeot' => ['peugeot'],
        'Fiat' => ['fiat'],
        'Suzuki' => ['suzuki'],
        'Mitsubishi' => ['mitsubishi'],
        'Seat' => ['seat'],
        'Mini' => ['mini cooper', 'mini'],
        'GMC' => ['gmc'],
        'Cadillac' => ['cadillac'],
        'Lincoln' => ['lincoln'],
        'Acura' => ['acura'],
        'Subaru' => ['subaru'],
        'Infiniti' => ['infiniti'],
        'Buick' => ['buick'],
        'Pontiac' => ['pontiac'],
        'MG' => ['mg'],
    ];

    /**
     * Valores comunes que NO representan una marca real de vehículo en el atributo BRAND de ML.
     *
     * @var array<int, string>
     */
    private const INVALID_BRAND_VALUES = [
        'generica', 'genérica', 'generico', 'genérico', 'taiwan', 'taiwán',
        'original', 'oem', 'mopar', 'bosch', 'valeo', 'denso', 'delphi',
        'otro', 'otra', 'sin marca', 'universal', 'varios', 'n/a', 'na',
        'aftermarket', 'depo', 'tyc',
    ];

    /**
     * Modelos frecuentes en el catálogo con su marca vehicular asociada (para desambiguar cuando BRAND venga sucio).
     *
     * @var array<string, string>
     */
    private const MODEL_TO_BRAND = [
        // Chevrolet
        'Silverado' => 'Chevrolet', 'Cheyenne' => 'Chevrolet', 'Avalanche' => 'Chevrolet',
        'Suburban' => 'Chevrolet', 'Tahoe' => 'Chevrolet', 'Colorado' => 'Chevrolet',
        'Aveo' => 'Chevrolet', 'Sonic' => 'Chevrolet', 'Cruze' => 'Chevrolet',
        'Beat' => 'Chevrolet', 'Spark' => 'Chevrolet', 'Matiz' => 'Chevrolet',
        'Trax' => 'Chevrolet', 'Equinox' => 'Chevrolet', 'Traverse' => 'Chevrolet',
        'Captiva' => 'Chevrolet', 'Malibu' => 'Chevrolet', 'Cavalier' => 'Chevrolet',
        'Tornado' => 'Chevrolet', 'Chevy' => 'Chevrolet', 'Express' => 'Chevrolet',
        'Optra' => 'Chevrolet', 'Onix' => 'Chevrolet', 'Tracker' => 'Chevrolet',
        'C10' => 'Chevrolet', 'S10' => 'Chevrolet', 'Blazer' => 'Chevrolet',
        // Nissan
        'Versa' => 'Nissan', 'Sentra' => 'Nissan', 'March' => 'Nissan',
        'Tiida' => 'Nissan', 'Tsuru' => 'Nissan', 'Kicks' => 'Nissan',
        'NP300' => 'Nissan', 'Frontier' => 'Nissan', 'Urvan' => 'Nissan',
        'Altima' => 'Nissan', 'Maxima' => 'Nissan', 'X-Trail' => 'Nissan',
        'Xtrail' => 'Nissan', 'Juke' => 'Nissan', 'Titan' => 'Nissan',
        'Armada' => 'Nissan', 'Note' => 'Nissan', 'Pathfinder' => 'Nissan',
        'Platina' => 'Nissan', 'Rogue' => 'Nissan', 'Murano' => 'Nissan',
        // Volkswagen
        'Jetta' => 'Volkswagen', 'Golf' => 'Volkswagen', 'Vento' => 'Volkswagen',
        'Polo' => 'Volkswagen', 'Passat' => 'Volkswagen', 'Tiguan' => 'Volkswagen',
        'Gol' => 'Volkswagen', 'Bora' => 'Volkswagen', 'Beetle' => 'Volkswagen',
        'Taos' => 'Volkswagen', 'Virtus' => 'Volkswagen', 'Amarok' => 'Volkswagen',
        'Pointer' => 'Volkswagen', 'Saveiro' => 'Volkswagen', 'Crossfox' => 'Volkswagen',
        // Ford
        'Figo' => 'Ford', 'Fiesta' => 'Ford', 'Focus' => 'Ford',
        'Fusion' => 'Ford', 'Escape' => 'Ford', 'Ecosport' => 'Ford',
        'Edge' => 'Ford', 'Explorer' => 'Ford', 'Expedition' => 'Ford',
        'Ranger' => 'Ford', 'Lobo' => 'Ford', 'F-150' => 'Ford',
        'F150' => 'Ford', 'Mustang' => 'Ford', 'Transit' => 'Ford',
        'Courier' => 'Ford', 'Ka' => 'Ford', 'Bronco' => 'Ford',
        // Toyota
        'Hilux' => 'Toyota', 'Corolla' => 'Toyota', 'Camry' => 'Toyota',
        'Yaris' => 'Toyota', 'RAV4' => 'Toyota', 'Sienna' => 'Toyota',
        'Tacoma' => 'Toyota', 'Tundra' => 'Toyota', 'Avanza' => 'Toyota',
        'Prius' => 'Toyota', 'Highlander' => 'Toyota', 'Hiace' => 'Toyota',
        // Honda
        'Civic' => 'Honda', 'Accord' => 'Honda', 'CR-V' => 'Honda',
        'CRV' => 'Honda', 'City' => 'Honda', 'Fit' => 'Honda',
        'HR-V' => 'Honda', 'Odyssey' => 'Honda', 'Pilot' => 'Honda',
        // Hyundai / Kia
        'Sonata' => 'Hyundai', 'Elantra' => 'Hyundai', 'Accent' => 'Hyundai',
        'Tucson' => 'Hyundai', 'Creta' => 'Hyundai', 'Santa Fe' => 'Hyundai',
        'Grand i10' => 'Hyundai', 'i10' => 'Hyundai',
        'Forte' => 'Kia', 'Rio' => 'Kia', 'Soul' => 'Kia',
        'Sportage' => 'Kia', 'Sorento' => 'Kia', 'Seltos' => 'Kia',
        // Mazda
        'CX-30' => 'Mazda', 'CX-3' => 'Mazda', 'CX-5' => 'Mazda',
        'CX-7' => 'Mazda', 'CX-9' => 'Mazda', 'Mazda 2' => 'Mazda',
        'Mazda 3' => 'Mazda', 'Mazda Tres' => 'Mazda', 'Mazda 6' => 'Mazda',
        // Dodge / Jeep / Chrysler / Ram
        'Attitude' => 'Dodge', 'Journey' => 'Dodge', 'Charger' => 'Dodge',
        'Challenger' => 'Dodge', 'Avenger' => 'Dodge', 'Caliber' => 'Dodge',
        'Neon' => 'Dodge', 'Dart' => 'Dodge', 'Durango' => 'Dodge',
        'Grand Cherokee' => 'Jeep', 'Cherokee' => 'Jeep', 'Compass' => 'Jeep',
        'Patriot' => 'Jeep', 'Wrangler' => 'Jeep', 'Renegade' => 'Jeep',
        'Promaster' => 'Ram', 'Ram 700' => 'Ram',
        // Europeos y otros
        'XC90' => 'Volvo', 'XC60' => 'Volvo', 'XC40' => 'Volvo',
        'V40' => 'Volvo', 'S40' => 'Volvo', 'S60' => 'Volvo',
        'Serie 3' => 'BMW', 'Serie 1' => 'BMW', 'Serie 5' => 'BMW',
        'X1' => 'BMW', 'X3' => 'BMW', 'X5' => 'BMW',
        'A1' => 'Audi', 'A3' => 'Audi', 'A4' => 'Audi',
        'Q3' => 'Audi', 'Q5' => 'Audi', 'Q7' => 'Audi',
        'Sandero' => 'Renault', 'Duster' => 'Renault', 'Logan' => 'Renault',
        'Stepway' => 'Renault', 'Koleos' => 'Renault', 'Oroch' => 'Renault',
        'Kangoo' => 'Renault', 'Kwid' => 'Renault', 'Fluence' => 'Renault',
        '206' => 'Peugeot', '207' => 'Peugeot', '208' => 'Peugeot',
        '301' => 'Peugeot', '307' => 'Peugeot', '308' => 'Peugeot',
        'Partner' => 'Peugeot',
        'Uno' => 'Fiat', 'Palio' => 'Fiat', 'Mobi' => 'Fiat',
        'Ducato' => 'Fiat', '500' => 'Fiat',
        'Swift' => 'Suzuki', 'Vitara' => 'Suzuki', 'Ignis' => 'Suzuki', 'SX4' => 'Suzuki',
        'Lancer' => 'Mitsubishi', 'Outlander' => 'Mitsubishi', 'Mirage' => 'Mitsubishi', 'L200' => 'Mitsubishi',
        'Ibiza' => 'Seat', 'Leon' => 'Seat', 'Toledo' => 'Seat', 'Arona' => 'Seat',
        'Giulietta' => 'Alfa Romeo',
    ];

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
        $sellerSku = $this->extractAttributeValue($rawAttributes, 'SELLER_SKU');
        $oemNumber = $this->extractAttributeValue($rawAttributes, 'OEM_NUMBER')
            ?? $this->extractAttributeValue($rawAttributes, 'OEM');

        $attrBrand = $this->extractAttributeValue($rawAttributes, 'BRAND');
        $attrModel = $this->extractAttributeValue($rawAttributes, 'MODEL');
        $attrYear = $this->extractAttributeValue($rawAttributes, 'VEHICLE_YEAR');

        [$brand, $model] = $this->resolveBrandAndModel($title, $attrBrand, $attrModel);
        [$yearStart, $yearEnd] = $this->extractYears($title, $attrYear);

        $category = $this->mapCategory($payload['category_id'] ?? null, $title);
        $dimensions = $this->resolveDimensions($payload, $rawAttributes, $category);

        [$side, $position] = $this->resolveSideAndPosition($title, $rawAttributes);

        $rawStock = (int) ($payload['available_quantity'] ?? 0);
        $meliStatus = (string) ($payload['status'] ?? 'active');
        $publishedStatus = ($meliStatus === 'active' && $rawStock > 0) ? 'published' : 'draft';

        $rawPrice = (float) ($payload['price'] ?? 0.0);
        $webPrice = round($rawPrice * $this->priceFactor, 2);

        $skuSeed = $sellerSku ?: $partNumber;
        $sku = ! empty($skuSeed)
            ? Str::limit(strtoupper(Str::slug($skuSeed)), 64, '')
            : Str::limit('JC-' . preg_replace('/[^A-Za-z0-9]/', '', $externalId), 64, '');

        $photos = $this->extractPictures($payload['pictures'] ?? []);
        $normalizedTitle = Str::limit($title, 255, '');
        $normalizedSlug = Str::limit(Str::slug("{$title}-{$externalId}"), 255, '');

        $attributes = [
            'mercadolibre_id' => Str::limit($externalId, 32, ''),
            'mercadolibre_permalink' => ! empty($payload['permalink']) ? Str::limit((string) $payload['permalink'], 500, '') : null,
            'title' => $normalizedTitle,
            'slug' => $normalizedSlug !== '' ? $normalizedSlug : Str::slug($externalId),
            'sku' => $sku !== '' ? $sku : ('JC-' . Str::random(8)),
            'part_number' => $partNumber !== null ? Str::limit($partNumber, 64, '') : null,
            'oem_number' => $oemNumber !== null ? Str::limit($oemNumber, 64, '') : null,
            'category' => $category,
            'vehicle_brand' => Str::limit($brand, 80, ''),
            'vehicle_model' => Str::limit($model, 100, ''),
            'year_start' => $yearStart,
            'year_end' => $yearEnd,
            'side' => $side !== null ? Str::limit($side, 32, '') : null,
            'position' => $position !== null ? Str::limit($position, 32, '') : null,
            'condition' => 'A',
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
     * Extrae el año inicial y final soportando 4 dígitos ("2015-2019") y 2 dígitos ("10-14", "96-99").
     *
     * @return array{0: int, 1: int}
     */
    public function extractYears(string $title, ?string $attributeYear): array
    {
        $currentYear = (int) date('Y');
        $maxPlausibleYear = $currentYear + 2;

        // 1. Rango de 4 dígitos: ej. "2015-2019", "2016 a 2022", "2015/2019"
        if (preg_match('/\b(19\d{2}|20\d{2})\s*(?:-|a|\/)\s*(19\d{2}|20\d{2})\b/i', $title, $matches)) {
            $y1 = (int) $matches[1];
            $y2 = (int) $matches[2];

            return [min($y1, $y2), max($y1, $y2)];
        }

        // 2. Rango de 2 dígitos: ej. "10-14", "01-07", "96-99", "15 a 19"
        if (preg_match('/(?<![.\d-])(\d{2})\s*(?:-|a|\/)\s*(\d{2})(?![.\d-])/i', $title, $matches)) {
            $y1 = $this->expandTwoDigitYear((int) $matches[1], $maxPlausibleYear);
            $y2 = $this->expandTwoDigitYear((int) $matches[2], $maxPlausibleYear);

            if ($y1 >= 1965 && $y2 <= $maxPlausibleYear && $y1 <= $y2 && ($y2 - $y1) <= 25) {
                return [$y1, $y2];
            }
        }

        // 3. Año único de 4 dígitos en título
        if (preg_match('/\b(19\d{2}|20\d{2})\b/', $title, $matches)) {
            $year = (int) $matches[1];
            if ($year >= 1965 && $year <= $maxPlausibleYear) {
                return [$year, $year];
            }
        }

        // 4. Atributo VEHICLE_YEAR
        if (! empty($attributeYear) && is_numeric($attributeYear)) {
            $year = (int) $attributeYear;
            if ($year >= 1965 && $year <= $maxPlausibleYear) {
                return [$year, $year];
            }
        }

        return [2015, $currentYear];
    }

    /**
     * Mapea el identificador de categoría de Mercado Libre a las categorías del catálogo.
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

        if ($categoryId === 'MLM187516' || str_contains($lowerTitle, 'faro') || str_contains($lowerTitle, 'calavera') || str_contains($lowerTitle, 'puerta') || str_contains($lowerTitle, 'cofre') || str_contains($lowerTitle, 'fascia') || str_contains($lowerTitle, 'radiador')) {
            return 'Colision';
        }

        if ($categoryId === 'MLM187519' || str_contains($lowerTitle, 'direccion') || str_contains($lowerTitle, 'dirección') || str_contains($lowerTitle, 'suspension') || str_contains($lowerTitle, 'suspensión') || str_contains($lowerTitle, 'cremallera') || str_contains($lowerTitle, 'horquilla')) {
            return 'Suspension';
        }

        if ($categoryId === 'MLM187517' || $categoryId === 'MLM187518' || str_contains($lowerTitle, 'alternador') || str_contains($lowerTitle, 'marcha') || str_contains($lowerTitle, 'sensor') || str_contains($lowerTitle, 'computadora') || str_contains($lowerTitle, 'compresor') || str_contains($lowerTitle, 'clima') || str_contains($lowerTitle, 'aire')) {
            return 'Electrico';
        }

        return 'Colision';
    }

    /**
     * Desglosa la cadena de envío "{alto}x{ancho}x{largo},{peso_gramos}" en medidas y peso en kg.
     *
     * @return array{height_cm: float, width_cm: float, length_cm: float, weight_kg: float}
     */
    public function parseDimensions(?string $dimensionsString, string $category): array
    {
        $defaults = $this->defaultDimensionsForCategory($category);

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
     * Resuelve dimensiones desde `shipping.dimensions` o atributos `SELLER_PACKAGE_*` reales de ML.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, array<string, mixed>>  $rawAttributes
     * @return array{height_cm: float, width_cm: float, length_cm: float, weight_kg: float}
     */
    private function resolveDimensions(array $payload, array $rawAttributes, string $category): array
    {
        $shippingDims = $payload['shipping']['dimensions'] ?? null;
        if (is_string($shippingDims) && str_contains($shippingDims, ',')) {
            return $this->parseDimensions($shippingDims, $category);
        }

        $defaults = $this->defaultDimensionsForCategory($category);

        $height = $this->parseMeasureToCm($this->extractAttributeValue($rawAttributes, 'SELLER_PACKAGE_HEIGHT'))
            ?? $defaults['height_cm'];
        $width = $this->parseMeasureToCm($this->extractAttributeValue($rawAttributes, 'SELLER_PACKAGE_WIDTH'))
            ?? $defaults['width_cm'];
        $length = $this->parseMeasureToCm($this->extractAttributeValue($rawAttributes, 'SELLER_PACKAGE_LENGTH'))
            ?? $defaults['length_cm'];
        $weightKg = $this->parseWeightToKg($this->extractAttributeValue($rawAttributes, 'SELLER_PACKAGE_WEIGHT'))
            ?? $defaults['weight_kg'];

        return [
            'height_cm' => max(1.0, $height),
            'width_cm' => max(1.0, $width),
            'length_cm' => max(1.0, $length),
            'weight_kg' => max(0.1, $weightKg),
        ];
    }

    /**
     * @return array{height_cm: float, width_cm: float, length_cm: float, weight_kg: float}
     */
    private function defaultDimensionsForCategory(string $category): array
    {
        return match ($category) {
            'Motores' => ['height_cm' => 65.0, 'width_cm' => 55.0, 'length_cm' => 70.0, 'weight_kg' => 85.0],
            'Transmisiones' => ['height_cm' => 45.0, 'width_cm' => 45.0, 'length_cm' => 80.0, 'weight_kg' => 60.0],
            'Partes Internas' => ['height_cm' => 20.0, 'width_cm' => 25.0, 'length_cm' => 50.0, 'weight_kg' => 18.0],
            'Suspension' => ['height_cm' => 20.0, 'width_cm' => 30.0, 'length_cm' => 100.0, 'weight_kg' => 12.0],
            'Electrico' => ['height_cm' => 20.0, 'width_cm' => 20.0, 'length_cm' => 25.0, 'weight_kg' => 5.0],
            default => ['height_cm' => 25.0, 'width_cm' => 30.0, 'length_cm' => 60.0, 'weight_kg' => 6.0],
        };
    }

    private function parseMeasureToCm(?string $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (! preg_match('/([\d.]+)\s*(mm|cm|m)?/i', $raw, $matches)) {
            return null;
        }

        $value = (float) $matches[1];
        if ($value <= 0) {
            return null;
        }

        $unit = strtolower($matches[2] ?? 'cm');

        return match ($unit) {
            'mm' => round($value / 10, 2),
            'm' => round($value * 100, 2),
            default => round($value, 2),
        };
    }

    private function parseWeightToKg(?string $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (! preg_match('/([\d.]+)\s*(kg|gr|g)?/i', $raw, $matches)) {
            return null;
        }

        $value = (float) $matches[1];
        if ($value <= 0) {
            return null;
        }

        $unit = strtolower($matches[2] ?? 'g');

        return match ($unit) {
            'kg' => round($value, 2),
            default => round($value / 1000, 2),
        };
    }

    /**
     * Resuelve marca y modelo limpios cruzando el título cuando los atributos de ML vengan contaminados.
     *
     * @return array{0: string, 1: string}
     */
    private function resolveBrandAndModel(string $title, ?string $attrBrand, ?string $attrModel): array
    {
        $titleBrand = $this->detectBrandFromTitle($title);
        $titleModel = $this->detectModelFromTitle($title);

        $cleanAttrBrand = $this->normalizeBrandAttribute($attrBrand);
        $cleanAttrModel = $this->normalizeModelAttribute($attrModel);

        // Prioridad marca: título explícito > marca inferida por modelo del título > atributo BRAND limpio > modelo atributo
        $brand = $titleBrand
            ?? ($titleModel !== null ? (self::MODEL_TO_BRAND[$titleModel] ?? null) : null)
            ?? $cleanAttrBrand
            ?? ($cleanAttrModel !== null ? ($this->inferBrandFromText($cleanAttrModel)) : null)
            ?? 'Universal';

        // Prioridad modelo: modelo detectado en título > atributo MODEL limpio > modelo mencionado dentro de BRAND sucio
        $model = $titleModel
            ?? $cleanAttrModel
            ?? ($attrBrand !== null ? $this->detectModelFromTitle($attrBrand) : null)
            ?? 'Varios';

        return [$brand, $model];
    }

    private function normalizeBrandAttribute(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        $lower = mb_strtolower($trimmed);
        if (in_array($lower, self::INVALID_BRAND_VALUES, true)) {
            return null;
        }

        // Si en BRAND pusieron un modelo (ej. "Malibu", "CAVALIER", "Jetta"), devolver la marca real de ese modelo
        foreach (self::MODEL_TO_BRAND as $modelName => $brandName) {
            if (mb_strtolower($modelName) === $lower) {
                return $brandName;
            }
        }

        foreach (self::BRAND_ALIASES as $canonical => $aliases) {
            if (mb_strtolower($canonical) === $lower || in_array($lower, $aliases, true)) {
                return $canonical;
            }
        }

        return null;
    }

    private function normalizeModelAttribute(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);
        if ($trimmed === '' || preg_match('/^(19|20)\d{2}$/', $trimmed) === 1) {
            return null;
        }

        $lower = mb_strtolower($trimmed);
        foreach (self::INVALID_BRAND_VALUES as $invalid) {
            if ($lower === $invalid) {
                return null;
            }
        }

        // Si viene sucio como "Generica Aveo  Izquierdo", intentar rescatar un modelo conocido dentro del texto
        if (str_word_count($trimmed) >= 2) {
            $detected = $this->detectModelFromTitle($trimmed);
            if ($detected !== null) {
                return $detected;
            }
        }

        return $trimmed;
    }

    private function inferBrandFromText(string $text): ?string
    {
        foreach (self::MODEL_TO_BRAND as $model => $brand) {
            if (strcasecmp($model, $text) === 0) {
                return $brand;
            }
        }

        return null;
    }

    /**
     * Resuelve lado y posición combinando `SIDE`, `POSITION`, `SIDE_POSITION` y pistas del título.
     *
     * @param  array<int, array<string, mixed>>  $rawAttributes
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveSideAndPosition(string $title, array $rawAttributes): array
    {
        $rawSide = $this->extractAttributeValue($rawAttributes, 'SIDE');
        $rawPos = $this->extractAttributeValue($rawAttributes, 'POSITION');
        $rawSidePos = $this->extractAttributeValue($rawAttributes, 'SIDE_POSITION');

        $side = $rawSide;
        $position = $rawPos;

        if ($side === null && $rawSidePos !== null) {
            $lowerSidePos = mb_strtolower($rawSidePos);
            $hasLeft = str_contains($lowerSidePos, 'izquierd') || str_contains($lowerSidePos, 'conductor');
            $hasRight = str_contains($lowerSidePos, 'derech') || str_contains($lowerSidePos, 'pasajero');

            if (($hasLeft && $hasRight) || str_contains($lowerSidePos, 'ambos')) {
                $side = 'Ambos lados';
            } elseif ($hasLeft || $hasRight) {
                $side = $rawSidePos;
            } elseif ($position === null && (str_contains($lowerSidePos, 'delanter') || str_contains($lowerSidePos, 'traser'))) {
                $position = $rawSidePos;
            }
        }

        $lowerTitle = mb_strtolower($title);
        if ($side === null) {
            if (preg_match('/\b(izquierdo|izquierda|izq|lh|piloto|conductor)\b/u', $lowerTitle) === 1) {
                $side = 'Izquierdo';
            } elseif (preg_match('/\b(derecho|derecha|der|rh|copiloto|pasajero)\b/u', $lowerTitle) === 1) {
                $side = 'Derecho';
            }
        }

        if ($position === null) {
            if (preg_match('/\b(delantero|delantera|del|frontal)\b/u', $lowerTitle) === 1) {
                $position = 'Delantero';
            } elseif (preg_match('/\b(trasero|trasera|tracero|tracera|tras)\b/u', $lowerTitle) === 1) {
                $position = 'Trasero';
            }
        }

        return [$side, $position];
    }

    private function expandTwoDigitYear(int $twoDigits, int $maxPlausibleYear): int
    {
        $threshold = ($maxPlausibleYear % 100);

        return $twoDigits <= $threshold ? (2000 + $twoDigits) : (1900 + $twoDigits);
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

    private function detectBrandFromTitle(string $title): ?string
    {
        foreach (self::BRAND_ALIASES as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                $pattern = '/\b' . preg_quote($alias, '/') . '\b/iu';
                if (preg_match($pattern, $title) === 1) {
                    return $canonical;
                }
            }
        }

        return null;
    }

    private function detectModelFromTitle(string $title): ?string
    {
        // Ordenar de más largo a más corto para evitar que "Ram" o "3" ganen antes que "Grand Cherokee" o "Mazda 3"
        $models = array_keys(self::MODEL_TO_BRAND);
        usort($models, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        foreach ($models as $model) {
            $pattern = '/\b' . preg_quote($model, '/') . '\b/iu';
            if (preg_match($pattern, $title) === 1) {
                return match ($model) {
                    'Mazda Tres' => 'Mazda 3',
                    'Xtrail' => 'X-Trail',
                    'F150' => 'F-150',
                    'CRV' => 'CR-V',
                    default => $model,
                };
            }
        }

        return null;
    }
}
