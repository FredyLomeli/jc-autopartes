<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CatalogService
{
    public const FILTER_OPTIONS_CACHE_KEY = 'catalog_filter_options_v1';

    private const FILTER_OPTIONS_TTL_SECONDS = 900;

    /**
     * Memoización por petición HTTP para evitar lecturas repetidas desde View::composer y controladores.
     *
     * @var array<string, mixed>|null
     */
    private ?array $memoizedFilterOptions = null;

    public function __construct(
        private readonly PriceService $priceService,
        private readonly StockService $stockService,
    ) {}

    /**
     * Limpia el caché de opciones de filtro cuando cambia el catálogo publicado.
     */
    public function clearFilterOptionsCache(): void
    {
        $this->memoizedFilterOptions = null;
        Cache::forget(self::FILTER_OPTIONS_CACHE_KEY);
    }

    /**
     * Obtiene las opciones dinámicas para los filtros de búsqueda sobre artículos publicados
     * mediante consultas SQL agregadas y cacheadas (evitando cargar todo el catálogo en memoria).
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
        if ($this->memoizedFilterOptions !== null) {
            return $this->memoizedFilterOptions;
        }

        $this->memoizedFilterOptions = Cache::remember(
            self::FILTER_OPTIONS_CACHE_KEY,
            self::FILTER_OPTIONS_TTL_SECONDS,
            fn (): array => $this->computeFilterOptions()
        );

        return $this->memoizedFilterOptions;
    }

    /**
     * @return array{
     *     brands: array<int, string>,
     *     models: array<int, string>,
     *     modelsByBrand: array<string, array<int, string>>,
     *     years: array<int, int>,
     *     categories: array<int, array{slug: string, name: string, warranty: string, desc: string, count: int}>,
     *     conditions: array<string, string>
     * }
     */
    private function computeFilterOptions(): array
    {
        $brands = Product::query()
            ->published()
            ->whereNotNull('vehicle_brand')
            ->where('vehicle_brand', '!=', '')
            ->distinct()
            ->orderBy('vehicle_brand')
            ->pluck('vehicle_brand')
            ->values()
            ->all();

        if (empty($brands)) {
            $brands = ['Nissan', 'Chevrolet', 'Toyota', 'Ford', 'Honda', 'Volkswagen'];
        }

        $brandModelPairs = Product::query()
            ->published()
            ->select(['vehicle_brand', 'vehicle_model'])
            ->whereNotNull('vehicle_model')
            ->where('vehicle_model', '!=', '')
            ->distinct()
            ->orderBy('vehicle_model')
            ->get();

        $models = $brandModelPairs
            ->pluck('vehicle_model')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $modelsByBrand = [];
        foreach ($brandModelPairs->groupBy('vehicle_brand') as $brand => $items) {
            if ($brand !== '') {
                $modelsByBrand[$brand] = $items->pluck('vehicle_model')->filter()->unique()->values()->all();
            }
        }

        $yearBounds = Product::query()
            ->published()
            ->selectRaw('MIN(year_start) as min_year, MAX(year_end) as max_year')
            ->first();

        $minYear = (int) ($yearBounds?->min_year ?: 2010);
        $maxYear = (int) ($yearBounds?->max_year ?: (int) date('Y'));
        $years = range(max($maxYear, (int) date('Y')), min($minYear, 2008));

        $categoryCounts = Product::query()
            ->published()
            ->selectRaw('category, COUNT(*) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category');

        $baseCategories = [
            ['slug' => 'Motores', 'name' => 'Motores Completos y 3/4', 'warranty' => 'Garantía por escrito', 'desc' => 'Motores verificados en compresión y funcionamiento listos para instalar.'],
            ['slug' => 'Transmisiones', 'name' => 'Transmisiones Automáticas y Estándar', 'warranty' => 'Garantía por escrito', 'desc' => 'Cajas de velocidades automáticas, CVT y manuales revisadas.'],
            ['slug' => 'Partes Internas', 'name' => 'Cabezas y Partes Internas', 'warranty' => 'Garantía por escrito', 'desc' => 'Cabezas de motor, cigüeñales, árboles de levas y múltiples.'],
            ['slug' => 'Colision', 'name' => 'Carrocería y Colisión', 'warranty' => 'Garantía por escrito', 'desc' => 'Cofres, puertas, salpicaderas, fascias, faros, calaveras y espejos.'],
            ['slug' => 'Suspension', 'name' => 'Suspensión y Dirección', 'warranty' => 'Garantía por escrito', 'desc' => 'Horquillas, amortiguadores, cajas de dirección, puentes y mangos.'],
            ['slug' => 'Electrico', 'name' => 'Sistema Eléctrico y Electrónico', 'warranty' => 'Garantía por escrito', 'desc' => 'Alternadores, marchas, módulos, cuerpos de aceleración y compresores.'],
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
     * Filtra y pagina los artículos publicados según los parámetros de la URL (renderizado inicial SSR).
     *
     * @param  array<string, mixed>  $filters
     */
    public function filterProducts(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->buildFilteredQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Filtra y obtiene lotes progresivos sin recalcular COUNT(*) total (ideal para Lazy Load).
     *
     * @param  array<string, mixed>  $filters
     */
    public function filterProductsBatch(array $filters, int $perPage = 12): Paginator
    {
        return $this->buildFilteredQuery($filters)
            ->simplePaginate($perPage)
            ->withQueryString();
    }

    /**
     * Construye la consulta filtrada y ordenada compartida entre SSR y lotes AJAX.
     *
     * @param  array<string, mixed>  $filters
     */
    private function buildFilteredQuery(array $filters): Builder
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
                    ->orWhere('part_number', 'like', "%{$search}%")
                    ->orWhere('vehicle_brand', 'like', "%{$search}%")
                    ->orWhere('vehicle_model', 'like', "%{$search}%");
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

        // Filtro de Promociones / Ofertas activas
        if (! empty($filters['ofertas'])) {
            $activePromos = Promotion::query()
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                })
                ->get();

            $hasGlobalPromo = $activePromos->contains(fn ($p) => $p->scope_type === 'all');

            if (! $hasGlobalPromo) {
                if ($activePromos->isEmpty()) {
                    $query->whereRaw('1 = 0');
                } else {
                    $categories = $activePromos->where('scope_type', 'category')->pluck('scope_value')->filter()->values();
                    $brands = $activePromos->where('scope_type', 'brand')->pluck('scope_value')->filter()->values();

                    $query->where(function ($sub) use ($categories, $brands) {
                        if ($categories->isNotEmpty()) {
                            $sub->orWhereIn('category', $categories);
                        }
                        if ($brands->isNotEmpty()) {
                            $sub->orWhereIn('vehicle_brand', $brands);
                        }
                    });
                }
            }
        }

        $sort = (string) ($filters['sort'] ?? 'recent');
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc')->orderByDesc('id'),
            'price_desc' => $query->orderBy('price', 'desc')->orderByDesc('id'),
            'title_asc' => $query->orderBy('title', 'asc'),
            default => $query->orderByDesc('is_featured')->orderByDesc('id'),
        };

        return $query;
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
