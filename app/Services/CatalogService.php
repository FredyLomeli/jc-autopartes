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

        $configuredCategories = config('catalog.categories', []);
        $aliases = config('catalog.category_aliases', []);

        $normalizedCounts = [];
        foreach ($categoryCounts as $rawName => $cnt) {
            $canonical = $aliases[$rawName] ?? $rawName;
            $normalizedCounts[$canonical] = ($normalizedCounts[$canonical] ?? 0) + (int) $cnt;
        }

        $categories = [];
        foreach ($configuredCategories as $slug => $meta) {
            $categories[] = [
                'slug' => $slug,
                'name' => $meta['name'] ?? $slug,
                'warranty' => $meta['warranty'] ?? 'Garantía por escrito',
                'desc' => $meta['desc'] ?? '',
                'count' => (int) ($normalizedCounts[$slug] ?? 0),
            ];
        }

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
            $this->applySearchFilter($query, (string) $filters['q']);
        }

        if (! empty($filters['category'])) {
            $requestedCategory = (string) $filters['category'];
            $aliases = config('catalog.category_aliases', []);
            $canonical = $aliases[$requestedCategory] ?? $requestedCategory;
            $matchingValues = array_values(array_unique(array_merge(
                [$requestedCategory, $canonical],
                array_keys($aliases, $canonical, true)
            )));

            $query->whereIn('category', $matchingValues);
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
     * Aplica búsqueda combinada por palabras (tokens AND entre palabras, OR entre columnas),
     * soportando orden libre, abreviaturas del ramo automotriz y años dentro del texto.
     */
    public function applySearchFilter(Builder $query, string $rawSearch, bool $includeYearMatch = true): void
    {
        $clean = trim($rawSearch);
        if ($clean === '') {
            return;
        }

        $rawTokens = preg_split('/\s+/u', $clean) ?: [];
        $stopWords = ['de', 'del', 'la', 'el', 'los', 'las', 'para', 'con', 'en', 'y'];

        $tokens = array_values(array_filter(
            $rawTokens,
            fn (string $t): bool => $t !== '' && (count($rawTokens) === 1 || ! in_array(mb_strtolower($t), $stopWords, true))
        ));

        if (empty($tokens)) {
            $tokens = array_values(array_filter($rawTokens, fn (string $t): bool => $t !== ''));
        }

        $tokens = array_slice($tokens, 0, 6);

        $abbreviations = [
            'chev' => ['chev', 'chevrolet'],
            'chevy' => ['chevy'],
            'vw' => ['vw', 'volkswagen'],
            'mb' => ['mb', 'mercedes', 'mercedes-benz'],
            'izq' => ['izq', 'izquierd', 'lh'],
            'lh' => ['lh', 'izq', 'izquierd'],
            'der' => ['der', 'derech', 'rh'],
            'rh' => ['rh', 'der', 'derech'],
            'del' => ['del', 'delanter'],
            'tras' => ['tras', 'traser'],
            'std' => ['std', 'estandar', 'estándar', 'manual'],
            'aut' => ['aut', 'automatic', 'automátic'],
            'ac' => ['ac', 'a/c', 'a/a', 'aire', 'clima'],
        ];

        $columns = [
            'title',
            'sku',
            'part_number',
            'oem_number',
            'mercadolibre_id',
            'vehicle_brand',
            'vehicle_model',
            'side',
            'position',
        ];

        foreach ($tokens as $token) {
            $lower = mb_strtolower($token);
            $variants = $abbreviations[$lower] ?? [$token];
            $isYear = $includeYearMatch && preg_match('/^(19|20)\d{2}$/', $token) === 1;
            $yearInt = $isYear ? (int) $token : null;

            $query->where(function (Builder $tokenGroup) use ($columns, $variants, $isYear, $yearInt) {
                foreach ($variants as $variant) {
                    $like = "%{$variant}%";
                    foreach ($columns as $column) {
                        $tokenGroup->orWhere($column, 'like', $like);
                    }
                }

                if ($isYear && $yearInt !== null) {
                    $tokenGroup->orWhere(function (Builder $yearRange) use ($yearInt) {
                        $yearRange->where('year_start', '<=', $yearInt)
                            ->where('year_end', '>=', $yearInt);
                    });
                }
            });
        }
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
