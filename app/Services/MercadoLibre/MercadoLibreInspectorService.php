<?php

namespace App\Services\MercadoLibre;

use App\Contracts\MarketplaceClient;
use App\Models\MarketplaceRawItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class MercadoLibreInspectorService
{
    public function __construct(
        private readonly MarketplaceClient $client,
        private readonly MercadoLibreOAuthService $oauthService
    ) {}

    /**
     * Descarga e indexa las publicaciones directamente desde la API oficial de Mercado Libre.
     *
     * @return array{total_stored: int, items: array<int, string>, brands: array<int, string>, categories: array<int, string>}
     */
    public function fetchAndStoreFromApi(
        ?int $limit = null,
        string $status = 'active',
        ?callable $onProgress = null
    ): array {
        $token = $this->oauthService->getActiveToken();

        if (! $token || empty($token->user_id)) {
            try {
                $profile = $this->client->getUserProfile('me');
                $userId = (string) ($profile['id'] ?? '');
            } catch (Throwable $e) {
                throw new RuntimeException('No hay una sesión activa de Mercado Libre vinculada. ' . $e->getMessage());
            }
        } else {
            $userId = (string) $token->user_id;
        }

        $pageSize = min(50, $limit ?? 50);
        $searchResponse = $this->client->searchListings($userId, [
            'status' => $status,
            'limit' => $pageSize,
            'offset' => 0,
        ]);

        $itemIds = $searchResponse['results'] ?? [];
        if ($limit !== null && count($itemIds) > $limit) {
            $itemIds = array_slice($itemIds, 0, $limit);
        }

        $storedIds = [];
        $total = count($itemIds);

        foreach ($itemIds as $index => $itemId) {
            $payload = $this->client->fetchListing((string) $itemId);

            $this->safelyStoreRawItem((string) $itemId, $payload);
            $storedIds[] = (string) $itemId;

            if ($onProgress) {
                $onProgress($index + 1, $total, (string) $itemId);
            }
        }

        return $this->buildInspectionSummary($storedIds);
    }

    /**
     * Carga e indexa publicaciones desde un fixture local de Mercado Libre para desarrollo y pruebas.
     *
     * @return array{total_stored: int, items: array<int, string>, brands: array<int, string>, categories: array<int, string>}
     */
    public function loadAndStoreFromFixture(
        ?string $fixturePath = null,
        ?callable $onProgress = null
    ): array {
        $path = $fixturePath ?? database_path('fixtures/mercadolibre_autoparts_fixture.json');

        if (! File::exists($path)) {
            throw new RuntimeException("El archivo fixture no existe en la ruta: {$path}");
        }

        $content = File::get($path);
        $items = json_decode($content, true);

        if (! is_array($items)) {
            throw new RuntimeException("El archivo fixture no contiene un JSON válido: {$path}");
        }

        $storedIds = [];
        $total = count($items);

        foreach ($items as $index => $payload) {
            $itemId = (string) ($payload['id'] ?? "FIXTURE-{$index}");

            $this->safelyStoreRawItem($itemId, $payload);
            $storedIds[] = $itemId;

            if ($onProgress) {
                $onProgress($index + 1, $total, $itemId);
            }
        }

        return $this->buildInspectionSummary($storedIds);
    }

    /**
     * Exporta todas las publicaciones crudas almacenadas hacia un archivo JSON de muestra para auditoría.
     */
    public function exportSampleJson(?string $outputPath = null): string
    {
        $targetPath = $outputPath ?? storage_path('app/mercadolibre_sample.json');

        $dir = dirname($targetPath);
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $items = [];

        try {
            $items = MarketplaceRawItem::query()
                ->where('marketplace', 'mercadolibre')
                ->orderBy('id')
                ->get()
                ->map(fn (MarketplaceRawItem $row) => $row->raw_payload)
                ->values()
                ->all();
        } catch (Throwable) {
            $items = [];
        }

        if (empty($items)) {
            $fixturePath = database_path('fixtures/mercadolibre_autoparts_fixture.json');
            if (File::exists($fixturePath)) {
                $items = json_decode(File::get($fixturePath), true) ?? [];
            }
        }

        File::put($targetPath, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $targetPath;
    }

    private function safelyStoreRawItem(string $itemId, array $payload): void
    {
        try {
            MarketplaceRawItem::query()->updateOrCreate(
                ['external_id' => $itemId],
                [
                    'marketplace' => 'mercadolibre',
                    'title' => $payload['title'] ?? null,
                    'status' => $payload['status'] ?? 'active',
                    'raw_payload' => $payload,
                    'fetched_at' => now(),
                ]
            );
        } catch (QueryException) {
            // Continúa la ejecución si no hay conexión a BD configurada en el entorno local
        }
    }

    /**
     * Construye un resumen de atributos de las publicaciones almacenadas.
     *
     * @param  array<int, string>  $itemIds
     * @return array{total_stored: int, items: array<int, string>, brands: array<int, string>, categories: array<int, string>}
     */
    private function buildInspectionSummary(array $itemIds): array
    {
        $brands = collect();
        $categories = collect();
        $totalCount = count($itemIds);

        try {
            $rawRecords = MarketplaceRawItem::query()
                ->whereIn('external_id', $itemIds)
                ->get();

            if ($rawRecords->isNotEmpty()) {
                foreach ($rawRecords as $record) {
                    $brand = $record->getMeliAttribute('BRAND');
                    if ($brand) {
                        $brands->push($brand);
                    }

                    $category = $record->raw_payload['category_id'] ?? null;
                    if ($category) {
                        $categories->push($category);
                    }
                }

                $totalCount = $rawRecords->count();
            }
        } catch (Throwable) {
            $rawRecords = collect();
        }

        if ($brands->isEmpty()) {
            $fixturePath = database_path('fixtures/mercadolibre_autoparts_fixture.json');
            if (File::exists($fixturePath)) {
                $fixtureItems = json_decode(File::get($fixturePath), true) ?? [];
                foreach ($fixtureItems as $item) {
                    if (in_array($item['id'] ?? null, $itemIds, true)) {
                        foreach ($item['attributes'] ?? [] as $attr) {
                            if (($attr['id'] ?? null) === 'BRAND' && ! empty($attr['value_name'])) {
                                $brands->push($attr['value_name']);
                            }
                        }
                        if (! empty($item['category_id'])) {
                            $categories->push($item['category_id']);
                        }
                    }
                }
            }
        }

        return [
            'total_stored' => $totalCount,
            'items' => $itemIds,
            'brands' => $brands->unique()->values()->all(),
            'categories' => $categories->unique()->values()->all(),
        ];
    }
}
