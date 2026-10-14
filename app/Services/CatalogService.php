<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CatalogService
{
    public function __construct(
        private readonly PriceService $priceService,
        private readonly StockService $stockService,
    ) {}

    /**
     * Obtiene las opciones dinámicas para los filtros de búsqueda sobre artículos publicados.
     *
     * @return array{
     *     brands: array<int, string>,
     *     models: array<int, string>,
     *     modelsByBrand: array<string, array<int, string>>,
     *     years: array<int, int>,
     *     categories: array<int, array{slug: string, name: string, warranty: string, desc: string, count: int}>,
     *     conditions: array<string, string>
     * }
     */
    public function getFilterOptions(): array
    {
        $published = Product::query()
            ->published()
            ->select(['category', 'vehicle_brand', 'vehicle_model', 'year_start', 'year_end'])
            ->get();

        $brands = $published->pluck('vehicle_brand')->filter()->unique()->sort()->values()->all();
        if (empty($brands)) {
            $brands = ['Nissan', 'Chevrolet', 'Toyota', 'Ford', 'Honda', 'Volkswagen'];
        }

        $models = $published->pluck('vehicle_model')->filter()->unique()->sort()->values()->all();

        $modelsByBrand = [];
        foreach ($published->groupBy('vehicle_brand') as $brand => $items) {
            if ($brand !== '') {
                $modelsByBrand[$brand] = $items->pluck('vehicle_model')->filter()->unique()->sort()->values()->all();
            }
        }

        $minYear = (int) ($published->min('year_start') ?: 2010);
        $maxYear = (int) ($published->max('year_end') ?: (int) date('Y'));
        $years = range(max($maxYear, (int) date('Y')), min($minYear, 2008));

        $categoryCounts = $published->groupBy('category')->map->count();

        $baseCategories = [
            ['slug' => 'Motores', 'name' => 'Motores Completos y 3/4', 'warranty' => '30 días de garantía', 'desc' => 'Motores verificados en compresión y funcionamiento listos para instalar.'],
            ['slug' => 'Transmisiones', 'name' => 'Transmisiones Automáticas y Estándar', 'warranty' => '30 días de garantía', 'desc' => 'Cajas de velocidades automáticas, CVT y manuales revisadas.'],
            ['slug' => 'Partes Internas', 'name' => 'Cabezas y Partes Internas', 'warranty' => '15 días de garantía', 'desc' => 'Cabezas de motor, cigüeñales, árboles de levas y múltiples.'],
            ['slug' => 'Colision', 'name' => 'Carrocería y Colisión', 'warranty' => '7 días de garantía', 'desc' => 'Cofres, puertas, salpicaderas, fascias, faros, calaveras y espejos.'],
            ['slug' => 'Suspension', 'name' => 'Suspensión y Dirección', 'warranty' => '7 días de garantía', 'desc' => 'Horquillas, amortiguadores, cajas de dirección, puentes y mangos.'],
            ['slug' => 'Electrico', 'name' => 'Sistema Eléctrico y Electrónico', 'warranty' => '7 días de garantía', 'desc' => 'Alternadores, marchas, módulos, cuerpos de aceleración y compresores.'],
        ];

        $categories = array_map(function (array $cat) use ($categoryCounts) {
            $cat['count'] = (int) ($categoryCounts[$cat['slug']] ?? 0);

            return $cat;
        }, $baseCategories);

        $conditions = [
            'A' => 'Grado A — Excelente estado',
            'B' => 'Grado B — Detalles estéticos menores',
            'C' => 'Grado C — Para reacondicionar',
        ];

        return [
            'brands' => $brands,
            'models' => $models,
            'modelsByBrand' => $modelsByBrand,
            'years' => $years,
            'categories' => $categories,
            'conditions' => $conditions,
        ];
    }

    /**
     * Columnas ligeras requeridas por las tarjetas del listado de catálogo.
     *
     * @var array<int, string>
     */
    private const LISTING_COLUMNS = [
        'id',
        'mercadolibre_id',
        'mercadolibre_permalink',
        'title',
        'slug',
        'sku',
        'part_number',
        'oem_number',
        'side',
        'position',
        'category',
        'vehicle_brand',
        'vehicle_model',
        'year_start',
        'year_end',
        'condition',
        'price',
        'stock',
        'status',
        'is_featured',
    ];

    /**
     * Obtiene las piezas destacadas publicadas para la portada.
     *
     * @return Collection<int, Product>
     */
    public function getFeaturedProducts(int $limit = 8): Collection
    {
        return Product::query()
            ->select(self::LISTING_COLUMNS)
            ->published()
            ->with('primaryPhoto')
            ->orderByDesc('is_featured')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Filtra y pagina los artículos publicados según los parámetros de la URL.
     *
     * @param  array<string, mixed>  $filters
     */
    public function filterProducts(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $query = Product::query()
            ->select(self::LISTING_COLUMNS)
            ->published()
            ->with('primaryPhoto');

        if (! empty($filters['q'])) {
            $search = trim((string) $filters['q']);
            $query->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('vehicle_brand', 'like', "%{$search}%")
                    ->orWhere('vehicle_model', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', (string) $filters['category']);
        }

        if (! empty($filters['brand'])) {
            $query->where('vehicle_brand', (string) $filters['brand']);
        }

        if (! empty($filters['model'])) {
            $query->where('vehicle_model', (string) $filters['model']);
        }

        if (! empty($filters['year']) && is_numeric($filters['year'])) {
            $year = (int) $filters['year'];
            $query->where('year_start', '<=', $year)
                ->where('year_end', '>=', $year);
        }

        if (! empty($filters['condition']) && in_array($filters['condition'], ['A', 'B', 'C'], true)) {
            $query->where('condition', (string) $filters['condition']);
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '' && is_numeric($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '' && is_numeric($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        $sort = (string) ($filters['sort'] ?? 'recent');
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc')->orderByDesc('id'),
            'price_desc' => $query->orderBy('price', 'desc')->orderByDesc('id'),
            'title_asc' => $query->orderBy('title', 'asc'),
            default => $query->orderByDesc('is_featured')->orderByDesc('id'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Obtiene un artículo publicado por su slug o lanza ModelNotFoundException (404).
     */
    public function getPublishedProductBySlug(string $slug): Product
    {
        return Product::query()
            ->published()
            ->with('photos')
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Obtiene refacciones relacionadas publicadas de la misma marca o categoría.
     *
     * @return Collection<int, Product>
     */
    public function getRelatedProducts(Product $product, int $limit = 4): Collection
    {
        return Product::query()
            ->select(self::LISTING_COLUMNS)
            ->published()
            ->with('primaryPhoto')
            ->where('id', '!=', $product->id)
            ->where(function ($q) use ($product) {
                $q->where('vehicle_brand', $product->vehicle_brand)
                    ->orWhere('category', $product->category);
            })
            ->orderByRaw('CASE WHEN vehicle_brand = ? THEN 0 ELSE 1 END', [$product->vehicle_brand])
            ->limit($limit)
            ->get();
    }

    /**
     * Construye el arreglo estructurado JSON-LD de Schema.org/Product para SEO.
     *
     * @return array<string, mixed>
     */
    public function buildProductJsonLd(Product $product): array
    {
        $unitPrice = $this->priceService->getUnitPrice($product);
        $hasStock = $this->stockService->hasStock($product, 1);

        $images = $product->photos->map(fn ($photo) => $photo->url)->values()->all();
        if (empty($images)) {
            $images = [$product->primary_photo_url];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->title,
            'sku' => $product->sku,
            'mpn' => $product->part_number ?? $product->sku,
            'description' => $product->description ?: "{$product->title} para {$product->vehicle_brand} {$product->vehicle_model} ({$product->year_start}-{$product->year_end}).",
            'category' => $product->category,
            'image' => $images,
            'brand' => [
                '@type' => 'Brand',
                'name' => $product->vehicle_brand,
            ],
            'itemCondition' => 'https://schema.org/UsedCondition',
            'offers' => [
                '@type' => 'Offer',
                'url' => route('catalog.show', $product->slug),
                'priceCurrency' => 'MXN',
                'price' => number_format($unitPrice, 2, '.', ''),
                'availability' => $hasStock
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/UsedCondition',
                'seller' => [
                    '@type' => 'AutoPartsStore',
                    'name' => config('business.name', 'JC Autopartes'),
                ],
            ],
        ];
    }
}
