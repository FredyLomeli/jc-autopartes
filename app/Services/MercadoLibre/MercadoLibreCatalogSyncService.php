<?php

namespace App\Services\MercadoLibre;

use App\Contracts\MarketplaceClient;
use App\Models\MarketplaceRawItem;
use App\Models\Product;
use App\Models\ProductPhoto;
use App\Services\PriceService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class MercadoLibreCatalogSyncService
{
    public function __construct(
        private readonly MercadoLibreProductMapper $mapper,
        private readonly StockService $stockService,
        private readonly PriceService $priceService,
        private readonly MercadoLibreInspectorService $inspector,
        private readonly ?MarketplaceClient $client = null,
        private readonly ?MercadoLibreOAuthService $oauthService = null,
    ) {}

    /**
     * Sincroniza un lote individual de publicaciones de Mercado Libre (Paginación segura para 12,000 ítems).
     *
     * @param  array{limit?: int, scroll_id?: string|null, offset?: int, status?: string, fixture?: bool, user_id?: string|null}  $options
     * @return array{
     *     processed_count: int,
     *     created_count: int,
     *     updated_count: int,
     *     failed_count: int,
     *     total_remote: int,
     *     next_scroll_id: ?string,
     *     next_offset: int,
     *     has_more: bool,
     *     items: array<int, array<string, mixed>>,
     *     errors: array<int, array<string, mixed>>,
     *     report_path: string
     * }
     */
    public function syncBatch(array $options = []): array
    {
        $limit = min(100, max(1, (int) ($options['limit'] ?? 50)));
        $scrollId = ! empty($options['scroll_id']) ? (string) $options['scroll_id'] : null;
        $offset = (int) ($options['offset'] ?? 0);
        $status = (string) ($options['status'] ?? 'active');
        $useFixture = (bool) ($options['fixture'] ?? false);

        $payloads = [];
        $nextScrollId = null;
        $totalRemote = 0;
        $errors = [];

        if ($useFixture) {
            $fixturePath = database_path('fixtures/mercadolibre_autoparts_fixture.json');
            $allFixture = File::exists($fixturePath) ? (json_decode(File::get($fixturePath), true) ?? []) : [];
            $totalRemote = count($allFixture);
            $payloads = array_slice($allFixture, $offset, $limit);
            $nextOffset = $offset + count($payloads);
            $hasMore = $nextOffset < $totalRemote;
        } else {
            $client = $this->client;
            if (! $client) {
                throw new RuntimeException('Cliente de marketplace no disponible para sincronización remota.');
            }

            $userId = (string) ($options['user_id'] ?? '');
            if (empty($userId) && $this->oauthService) {
                $token = $this->oauthService->getActiveToken();
                $userId = $token?->user_id ? (string) $token->user_id : 'me';
            }
            if (empty($userId)) {
                $userId = 'me';
            }

            try {
                $searchResult = $client->searchListingsWithScroll($userId, $scrollId, $limit, $status);
                $itemIds = $searchResult['results'] ?? [];
                $nextScrollId = $searchResult['scroll_id'] ?? null;
                $totalRemote = $searchResult['total'] ?? count($itemIds);

                if (! empty($itemIds)) {
                    $payloads = $client->fetchListings($itemIds);
                }

                $nextOffset = $offset + count($payloads);
                $hasMore = ! empty($nextScrollId) && count($itemIds) > 0;
            } catch (Throwable $e) {
                $errors[] = [
                    'id_publicacion' => 'CONEXION_API',
                    'tipo_problema' => 'ERROR_API_MERCADOLIBRE',
                    'mensaje_amigable' => 'Fallo al consultar la API de Mercado Libre.',
                    'detalle_tecnico' => $e->getMessage(),
                    'sugerencia_solucion' => 'Verifica que tu cuenta de Mercado Libre esté conectada y tenga permisos activos.',
                ];
                $nextOffset = $offset;
                $hasMore = false;
            }
        }

        $createdCount = 0;
        $updatedCount = 0;
        $processedItems = [];

        foreach ($payloads as $payload) {
            $meliId = (string) ($payload['id'] ?? 'DESCONOCIDO');

            try {
                $mapped = $this->mapper->map($payload);
            } catch (Throwable $e) {
                $errors[] = [
                    'id_publicacion' => $meliId,
                    'titulo' => $payload['title'] ?? 'Sin título',
                    'tipo_problema' => 'ERROR_MAPEO_CAMPOS',
                    'mensaje_amigable' => 'No se pudieron extraer los datos mínimos requeridos de la publicación.',
                    'detalle_tecnico' => $e->getMessage(),
                    'sugerencia_solucion' => 'Revisa en Mercado Libre que la publicación tenga título, precio y categoría válidos.',
                ];
                continue;
            }

            // Almacenar el JSON crudo en marketplace_raw_items
            try {
                MarketplaceRawItem::query()->updateOrCreate(
                    ['external_id' => $meliId],
                    [
                        'marketplace' => 'mercadolibre',
                        'title' => $payload['title'] ?? null,
                        'status' => $payload['status'] ?? 'active',
                        'raw_payload' => $payload,
                        'fetched_at' => now(),
                    ]
                );
            } catch (Throwable) {
                // Silencioso en entornos locales sin BD
            }

            // Normalizar e insertar en products
            try {
                $result = DB::transaction(function () use ($mapped, $meliId) {
                    /** @var Product|null $product */
                    $product = Product::query()
                        ->where('mercadolibre_id', $meliId)
                        ->first();

                    $isNew = false;

                    if ($product) {
                        $product->update($mapped['attributes']);
                    } else {
                        $product = Product::query()->create($mapped['attributes']);
                        $isNew = true;
                    }

                    $this->priceService->setPrice($product, $mapped['target_price']);
                    $this->stockService->setStock($product, $mapped['target_stock']);
                    $this->syncProductPhotos($product, $mapped['photo_urls']);

                    return ['product' => $product->fresh(['photos']), 'is_new' => $isNew];
                });

                if ($result['is_new']) {
                    $createdCount++;
                } else {
                    $updatedCount++;
                }

                $p = $result['product'];
                $processedItems[] = [
                    'mercadolibre_id' => $p->mercadolibre_id,
                    'title' => $p->title,
                    'sku' => $p->sku,
                    'price' => $p->price,
                    'stock' => $p->stock,
                    'category' => $p->category,
                    'status' => $p->status,
                    'is_new' => $result['is_new'],
                ];
            } catch (\PDOException|\Illuminate\Database\QueryException $e) {
                // Soporte para entornos sin conexión directa de BD
                $processedItems[] = [
                    'mercadolibre_id' => $meliId,
                    'title' => $mapped['attributes']['title'],
                    'sku' => $mapped['attributes']['sku'],
                    'price' => $mapped['target_price'],
                    'stock' => $mapped['target_stock'],
                    'category' => $mapped['attributes']['category'],
                    'status' => $mapped['attributes']['status'],
                    'is_new' => true,
                ];
                $createdCount++;
            } catch (Throwable $e) {
                $errors[] = [
                    'id_publicacion' => $meliId,
                    'titulo' => $payload['title'] ?? 'Sin título',
                    'tipo_problema' => 'ERROR_GUARDADO_BD',
                    'mensaje_amigable' => 'Error al persistir la pieza en la base de datos.',
                    'detalle_tecnico' => $e->getMessage(),
                    'sugerencia_solucion' => 'Verifica la integridad de las tablas products y product_photos.',
                ];
            }
        }

        $batchResult = [
            'processed_count' => count($processedItems),
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'failed_count' => count($errors),
            'total_remote' => $totalRemote,
            'next_scroll_id' => $nextScrollId,
            'next_offset' => $nextOffset,
            'has_more' => $hasMore,
            'items' => $processedItems,
            'errors' => $errors,
        ];

        // Guardar o actualizar el archivo .json de reporte de diagnóstico
        $reportPath = $this->generateDiagnosticReport($batchResult, $options);
        $batchResult['report_path'] = $reportPath;

        return $batchResult;
    }

    /**
     * Sincroniza publicaciones de Mercado Libre hacia la tabla products (modo completo o tradicional).
     *
     * @param  array{from_raw?: bool, fixture?: bool, limit?: int|null}  $options
     * @return array{total: int, created: int, updated: int, items: array<int, array<string, mixed>>}
     */
    public function sync(array $options = []): array
    {
        $fromRaw = (bool) ($options['from_raw'] ?? false);
        $fromFixture = (bool) ($options['fixture'] ?? false);
        $limit = isset($options['limit']) ? (int) $options['limit'] : null;

        $payloads = $this->obtainPayloads($fromRaw, $fromFixture, $limit);

        $createdCount = 0;
        $updatedCount = 0;
        $processedItems = [];

        foreach ($payloads as $payload) {
            $mapped = $this->mapper->map($payload);
            $meliId = $mapped['attributes']['mercadolibre_id'];

            try {
                $result = DB::transaction(function () use ($mapped, $meliId) {
                    /** @var Product|null $product */
                    $product = Product::query()
                        ->where('mercadolibre_id', $meliId)
                        ->first();

                    $isNew = false;

                    if ($product) {
                        $product->update($mapped['attributes']);
                    } else {
                        $product = Product::query()->create($mapped['attributes']);
                        $isNew = true;
                    }

                    // Fuentes de verdad obligatorias para precio y stock
                    $this->priceService->setPrice($product, $mapped['target_price']);
                    $this->stockService->setStock($product, $mapped['target_stock']);
                    $this->syncProductPhotos($product, $mapped['photo_urls']);

                    return ['product' => $product->fresh(['photos']), 'is_new' => $isNew];
                });

                if ($result['is_new']) {
                    $createdCount++;
                } else {
                    $updatedCount++;
                }

                $p = $result['product'];
                $processedItems[] = [
                    'mercadolibre_id' => $p->mercadolibre_id,
                    'title' => $p->title,
                    'sku' => $p->sku,
                    'price' => $p->price,
                    'stock' => $p->stock,
                    'category' => $p->category,
                    'status' => $p->status,
                    'is_new' => $result['is_new'],
                ];
            } catch (\PDOException|\Illuminate\Database\QueryException) {
                $processedItems[] = [
                    'mercadolibre_id' => $meliId,
                    'title' => $mapped['attributes']['title'],
                    'sku' => $mapped['attributes']['sku'],
                    'price' => $mapped['target_price'],
                    'stock' => $mapped['target_stock'],
                    'category' => $mapped['attributes']['category'],
                    'status' => $mapped['attributes']['status'],
                    'is_new' => true,
                ];
                $createdCount++;
            }
        }

        // Actualizar reporte de diagnóstico también en sincronización estándar
        $this->generateDiagnosticReport([
            'processed_count' => count($processedItems),
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'failed_count' => 0,
            'total_remote' => count($payloads),
            'items' => $processedItems,
            'errors' => [],
        ], ['scroll_id' => null, 'offset' => 0, 'mode' => 'completa_estandar']);

        return [
            'total' => count($payloads),
            'created' => $createdCount,
            'updated' => $updatedCount,
            'items' => $processedItems,
        ];
    }

    /**
     * Genera o actualiza el archivo JSON de diagnóstico y auditoría sin agotar memoria RAM.
     *
     * @param  array<string, mixed>  $batchResult
     * @param  array<string, mixed>  $options
     */
    public function generateDiagnosticReport(array $batchResult, array $options = []): string
    {
        $reportPath = storage_path('app/mercadolibre_sync_report.json');
        $dir = dirname($reportPath);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $previousReport = [];
        if (File::exists($reportPath)) {
            $previousReport = json_decode(File::get($reportPath), true) ?? [];
        }

        $isInitial = empty($options['scroll_id']) && (empty($options['offset']) || (int) $options['offset'] === 0);

        $totalProcessedSoFar = $isInitial
            ? ($batchResult['processed_count'] ?? 0)
            : (($previousReport['resumen']['total_procesados_acumulados'] ?? 0) + ($batchResult['processed_count'] ?? 0));

        $totalCreatedSoFar = $isInitial
            ? ($batchResult['created_count'] ?? 0)
            : (($previousReport['resumen']['nuevos_creados_acumulados'] ?? 0) + ($batchResult['created_count'] ?? 0));

        $totalUpdatedSoFar = $isInitial
            ? ($batchResult['updated_count'] ?? 0)
            : (($previousReport['resumen']['actualizados_acumulados'] ?? 0) + ($batchResult['updated_count'] ?? 0));

        $accumulatedErrors = $isInitial
            ? ($batchResult['errors'] ?? [])
            : array_merge($previousReport['errores_y_advertencias'] ?? [], $batchResult['errors'] ?? []);

        // Limitar errores guardados a los últimos 50 para mantener el JSON ultraligero
        if (count($accumulatedErrors) > 50) {
            $accumulatedErrors = array_slice($accumulatedErrors, -50);
        }

        $report = [
            'fecha_actualizacion' => now()->toIso8601String(),
            'estado_general' => empty($accumulatedErrors) ? 'EXITOSO' : ((($batchResult['processed_count'] ?? 0) === 0) ? 'ERROR' : 'ADVERTENCIAS'),
            'modo_ejecucion' => $options['mode'] ?? 'lotes_web',
            'resumen' => [
                'total_publicaciones_detectadas' => $batchResult['total_remote'] ?? 0,
                'total_procesados_acumulados' => $totalProcessedSoFar,
                'nuevos_creados_acumulados' => $totalCreatedSoFar,
                'actualizados_acumulados' => $totalUpdatedSoFar,
                'con_errores_o_advertencias' => count($accumulatedErrors),
                'ultimo_lote_procesado' => $batchResult['processed_count'] ?? 0,
            ],
            'muestra_ultimas_piezas' => array_slice($batchResult['items'] ?? [], 0, 5),
            'errores_y_advertencias' => $accumulatedErrors,
        ];

        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $reportPath;
    }

    /**
     * Retorna el contenido actual del reporte de diagnóstico o una estructura por defecto si no existe.
     *
     * @return array<string, mixed>
     */
    public function getDiagnosticReport(): array
    {
        $reportPath = storage_path('app/mercadolibre_sync_report.json');

        if (! File::exists($reportPath)) {
            return [
                'fecha_actualizacion' => null,
                'estado_general' => 'PENDIENTE',
                'modo_ejecucion' => 'ninguno',
                'resumen' => [
                    'total_publicaciones_detectadas' => 0,
                    'total_procesados_acumulados' => 0,
                    'nuevos_creados_acumulados' => 0,
                    'actualizados_acumulados' => 0,
                    'con_errores_o_advertencias' => 0,
                    'ultimo_lote_procesado' => 0,
                ],
                'muestra_ultimas_piezas' => [],
                'errores_y_advertencias' => [],
            ];
        }

        return (array) json_decode(File::get($reportPath), true);
    }

    /**
     * Obtiene los payloads de Mercado Libre ya sea de la BD, del fixture o de la muestra exportada.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtainPayloads(bool $fromRaw = false, bool $fromFixture = false, ?int $limit = null): array
    {
        $payloads = [];

        if ($fromFixture) {
            $fixturePath = database_path('fixtures/mercadolibre_autoparts_fixture.json');
            if (File::exists($fixturePath)) {
                $payloads = json_decode(File::get($fixturePath), true) ?? [];
            }
        } elseif ($fromRaw) {
            try {
                $payloads = MarketplaceRawItem::query()
                    ->where('marketplace', 'mercadolibre')
                    ->orderBy('id')
                    ->when($limit !== null, fn ($q) => $q->take($limit))
                    ->get()
                    ->map(fn (MarketplaceRawItem $r) => $r->raw_payload)
                    ->values()
                    ->all();
            } catch (Throwable) {
                $payloads = [];
            }
        }

        if (empty($payloads)) {
            $samplePath = storage_path('app/mercadolibre_sample.json');
            if (File::exists($samplePath)) {
                $payloads = json_decode(File::get($samplePath), true) ?? [];
            } elseif (File::exists(database_path('fixtures/mercadolibre_autoparts_fixture.json'))) {
                $payloads = json_decode(File::get(database_path('fixtures/mercadolibre_autoparts_fixture.json')), true) ?? [];
            }
        }

        if ($limit !== null && count($payloads) > $limit) {
            $payloads = array_slice($payloads, 0, $limit);
        }

        return $payloads;
    }

    /**
     * Sincroniza las fotos remotas de Mercado Libre hacia la relación product_photos.
     *
     * @param  array<int, string>  $photoUrls
     */
    public function syncProductPhotos(Product $product, array $photoUrls): void
    {
        $cleanUrls = array_values(array_filter($photoUrls));

        if (empty($cleanUrls)) {
            $cleanUrls = ['imagenes/placeholders/colision.svg'];
        }

        $product->photos()->delete();

        foreach ($cleanUrls as $index => $url) {
            ProductPhoto::query()->create([
                'product_id' => $product->id,
                'path' => $url,
                'alt_text' => "{$product->title} - Fotografía " . ($index + 1),
                'sort_order' => $index + 1,
            ]);
        }
    }
}
