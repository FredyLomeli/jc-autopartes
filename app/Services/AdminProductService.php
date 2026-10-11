<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPhoto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Servicio de gestión administrativa de artículos (/admin/articulos).
 *
 * Toda lectura y actualización de precios y existencias se delega estrictamente a
 * `App\Services\PriceService` y `App\Services\StockService`.
 */
class AdminProductService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly PriceService $priceService,
    ) {}

    /**
     * Columnas ligeras requeridas en el listado administrativo.
     *
     * @var array<int, string>
     */
    private const LISTING_COLUMNS = [
        'id',
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
        'status',
        'is_featured',
    ];

    /**
     * Lista paginada y filtrada de artículos para la tabla de /admin/articulos.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getPaginatedProducts(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Product::query()
            ->select(self::LISTING_COLUMNS)
            ->with('primaryPhoto');

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('vehicle_brand', 'like', "%{$search}%")
                    ->orWhere('vehicle_model', 'like', "%{$search}%");
            });
        }

        $category = trim((string) ($filters['category'] ?? ''));
        if ($category !== '') {
            $query->where('category', $category);
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if (in_array($status, ['published', 'draft'], true)) {
            $query->where('status', $status);
        }

        return $query
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Product $product) {
                $unitPrice = $this->priceService->getUnitPrice($product);
                $availableStock = $this->stockService->getAvailableStock($product);

                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'title' => $product->title,
                    'slug' => $product->slug,
                    'category' => $product->category,
                    'vehicle_brand' => $product->vehicle_brand,
                    'vehicle_model' => $product->vehicle_model,
                    'year_start' => $product->year_start,
                    'year_end' => $product->year_end,
                    'condition' => $product->condition,
                    'warranty_days' => $product->warrantyDays(),
                    'price' => $unitPrice,
                    'formatted_price' => $this->priceService->format($unitPrice),
                    'stock' => $availableStock,
                    'has_stock' => $this->stockService->hasStock($product),
                    'status' => $product->status,
                    'is_featured' => (bool) $product->is_featured,
                    'primary_photo_url' => $product->primary_photo_url,
                ];
            });
    }

    /**
     * Catálogos y opciones para filtros y formulario de alta/edición.
     *
     * @return array<string, mixed>
     */
    public function getFilterAndFormOptions(): array
    {
        return [
            'categories' => [
                'Motores',
                'Transmisiones',
                'Partes Internas',
                'Suspensión y Dirección',
                'Colisión y Carrocería',
                'Eléctrico y Sensores',
                'Enfriamiento',
                'Frenos',
            ],
            'conditions' => [
                ['value' => 'A', 'label' => 'Grado A — Excelente estado estético y funcional'],
                ['value' => 'B', 'label' => 'Grado B — Buen estado funcional con detalles menores'],
                ['value' => 'C', 'label' => 'Grado C — Funcional para reacondicionar o despiece'],
            ],
            'statuses' => [
                ['value' => 'published', 'label' => 'Publicado (Visible en tienda)'],
                ['value' => 'draft', 'label' => 'Borrador (Oculto al público)'],
            ],
            'brands' => [
                'Nissan',
                'Chevrolet',
                'Volkswagen',
                'Ford',
                'Toyota',
                'Honda',
                'Mazda',
                'Kia',
                'Hyundai',
                'Dodge',
                'Jeep',
            ],
        ];
    }

    /**
     * Formatea un artículo para su edición en el formulario de Vue.
     *
     * @return array<string, mixed>
     */
    public function formatProductForForm(Product $product): array
    {
        $product->loadMissing('photos');

        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'title' => $product->title,
            'slug' => $product->slug,
            'category' => $product->category,
            'vehicle_brand' => $product->vehicle_brand,
            'vehicle_model' => $product->vehicle_model,
            'year_start' => (int) $product->year_start,
            'year_end' => (int) $product->year_end,
            'condition' => $product->condition,
            'price' => $this->priceService->getUnitPrice($product),
            'stock' => $this->stockService->getAvailableStock($product),
            'description' => $product->description ?? '',
            'weight_kg' => $product->weight_kg !== null ? (float) $product->weight_kg : null,
            'height_cm' => $product->height_cm !== null ? (float) $product->height_cm : null,
            'width_cm' => $product->width_cm !== null ? (float) $product->width_cm : null,
            'length_cm' => $product->length_cm !== null ? (float) $product->length_cm : null,
            'status' => $product->status,
            'is_featured' => (bool) $product->is_featured,
            'photo_urls' => $product->photos->map(fn (ProductPhoto $photo) => $photo->path)->values()->all(),
            'photos_preview' => $product->photos->map(fn (ProductPhoto $photo) => [
                'id' => $photo->id,
                'path' => $photo->path,
                'url' => $photo->url,
            ])->values()->all(),
        ];
    }

    /**
     * Crea un nuevo artículo delegando el precio a PriceService y la existencia a StockService.
     *
     * @param  array<string, mixed>  $validated
     * @param  array<int, UploadedFile>  $uploadedPhotos
     */
    public function createProduct(array $validated, array $uploadedPhotos = []): Product
    {
        return DB::transaction(function () use ($validated, $uploadedPhotos) {
            $slug = $this->generateUniqueSlug(
                $validated['slug'] ?? null,
                (string) $validated['title'],
                (string) $validated['sku']
            );

            $product = Product::query()->create([
                'sku' => strtoupper(trim((string) $validated['sku'])),
                'title' => trim((string) $validated['title']),
                'slug' => $slug,
                'category' => (string) $validated['category'],
                'vehicle_brand' => trim((string) $validated['vehicle_brand']),
                'vehicle_model' => trim((string) $validated['vehicle_model']),
                'year_start' => (int) $validated['year_start'],
                'year_end' => (int) $validated['year_end'],
                'condition' => (string) $validated['condition'],
                'price' => 0,
                'stock' => 0,
                'description' => $validated['description'] ?? null,
                'weight_kg' => $validated['weight_kg'] ?? null,
                'height_cm' => $validated['height_cm'] ?? null,
                'width_cm' => $validated['width_cm'] ?? null,
                'length_cm' => $validated['length_cm'] ?? null,
                'status' => (string) $validated['status'],
                'is_featured' => (bool) ($validated['is_featured'] ?? false),
            ]);

            // Única fuente de verdad para precio y existencia
            $this->priceService->setPrice($product, (float) $validated['price']);
            $this->stockService->setStock($product, (int) $validated['stock']);

            $this->syncProductPhotos(
                $product,
                $validated['photo_urls'] ?? [],
                $uploadedPhotos
            );

            return $product->fresh(['photos']);
        });
    }

    /**
     * Actualiza un artículo existente delegando el precio a PriceService y la existencia a StockService.
     *
     * @param  array<string, mixed>  $validated
     * @param  array<int, UploadedFile>  $uploadedPhotos
     */
    public function updateProduct(Product $product, array $validated, array $uploadedPhotos = []): Product
    {
        return DB::transaction(function () use ($product, $validated, $uploadedPhotos) {
            $slug = $this->generateUniqueSlug(
                $validated['slug'] ?? $product->slug,
                (string) $validated['title'],
                (string) $validated['sku'],
                $product->id
            );

            $product->update([
                'sku' => strtoupper(trim((string) $validated['sku'])),
                'title' => trim((string) $validated['title']),
                'slug' => $slug,
                'category' => (string) $validated['category'],
                'vehicle_brand' => trim((string) $validated['vehicle_brand']),
                'vehicle_model' => trim((string) $validated['vehicle_model']),
                'year_start' => (int) $validated['year_start'],
                'year_end' => (int) $validated['year_end'],
                'condition' => (string) $validated['condition'],
                'description' => $validated['description'] ?? null,
                'weight_kg' => $validated['weight_kg'] ?? null,
                'height_cm' => $validated['height_cm'] ?? null,
                'width_cm' => $validated['width_cm'] ?? null,
                'length_cm' => $validated['length_cm'] ?? null,
                'status' => (string) $validated['status'],
                'is_featured' => (bool) ($validated['is_featured'] ?? false),
            ]);

            // Única fuente de verdad para precio y existencia
            $this->priceService->setPrice($product, (float) $validated['price']);
            $this->stockService->setStock($product, (int) $validated['stock']);

            if (array_key_exists('photo_urls', $validated) || ! empty($uploadedPhotos)) {
                $this->syncProductPhotos(
                    $product,
                    $validated['photo_urls'] ?? [],
                    $uploadedPhotos
                );
            }

            return $product->fresh(['photos']);
        });
    }

    /**
     * Elimina un artículo y sus fotografías asociadas.
     */
    public function deleteProduct(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $product->photos()->delete();
            $product->delete();
        });
    }

    /**
     * Sincroniza las fotografías del artículo (tanto URLs/rutas como archivos subidos).
     *
     * @param  array<int, string|null>  $photoUrls
     * @param  array<int, UploadedFile>  $uploadedPhotos
     */
    private function syncProductPhotos(Product $product, array $photoUrls, array $uploadedPhotos): void
    {
        $cleanPaths = collect($photoUrls)
            ->map(fn ($url) => trim((string) $url))
            ->filter(fn (string $url) => $url !== '')
            ->values()
            ->all();

        foreach ($uploadedPhotos as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $stored = $file->store('products', 'public');
                if ($stored) {
                    $cleanPaths[] = 'storage/' . ltrim($stored, '/');
                }
            }
        }

        if (empty($cleanPaths)) {
            $cleanPaths = [$this->defaultPlaceholderForCategory($product->category)];
        }

        $product->photos()->delete();

        foreach (array_values($cleanPaths) as $index => $path) {
            ProductPhoto::query()->create([
                'product_id' => $product->id,
                'path' => $path,
                'alt_text' => "{$product->title} - Imagen " . ($index + 1),
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function generateUniqueSlug(?string $customSlug, string $title, string $sku, ?int $ignoreId = null): string
    {
        $base = trim((string) $customSlug) !== ''
            ? Str::slug((string) $customSlug)
            : Str::slug($title . '-' . $sku);

        if ($base === '') {
            $base = 'refaccion-' . strtolower(Str::random(6));
        }

        $slug = $base;
        $counter = 2;

        while (
            Product::query()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function defaultPlaceholderForCategory(string $category): string
    {
        return match ($category) {
            'Motores', 'Partes Internas', 'Enfriamiento' => 'imagenes/placeholders/motor.svg',
            'Transmisiones' => 'imagenes/placeholders/transmision.svg',
            'Suspensión y Dirección', 'Frenos' => 'imagenes/placeholders/suspension.svg',
            'Eléctrico y Sensores' => 'imagenes/placeholders/electrico.svg',
            default => 'imagenes/placeholders/colision.svg',
        };
    }
}
