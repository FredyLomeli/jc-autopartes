<?php

namespace App\Services\MercadoLibre;

use App\Contracts\MarketplaceClient;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MercadoLibreClient implements MarketplaceClient
{
    private readonly string $apiBaseUrl;

    public function __construct(
        private readonly MercadoLibreOAuthService $oauthService
    ) {
        $this->apiBaseUrl = rtrim((string) config('mercadolibre.api_base_url', 'https://api.mercadolibre.com'), '/');
    }

    /**
     * Obtiene el detalle completo de una publicación en Mercado Libre (/items/{id}).
     *
     * @return array<string, mixed>
     */
    public function fetchListing(string $externalId): array
    {
        $response = $this->authenticatedRequest()->get("{$this->apiBaseUrl}/items/{$externalId}");

        if ($response->failed()) {
            $this->handleApiError($response, "Error al consultar la publicación {$externalId} en Mercado Libre");
        }

        return (array) $response->json();
    }

    /**
     * Obtiene en bloque (multi-get) el detalle de múltiples publicaciones en lotes de hasta 20 ítems (/items?ids=...).
     *
     * @param  array<int, string>  $externalIds
     * @return array<int, array<string, mixed>>
     */
    public function fetchListings(array $externalIds): array
    {
        if (empty($externalIds)) {
            return [];
        }

        $allListings = [];
        $chunks = array_chunk(array_values(array_unique($externalIds)), 20);

        foreach ($chunks as $chunk) {
            $idsParam = implode(',', $chunk);
            $response = $this->authenticatedRequest()->get("{$this->apiBaseUrl}/items", [
                'ids' => $idsParam,
            ]);

            if ($response->successful()) {
                $items = $response->json();
                if (is_array($items)) {
                    foreach ($items as $entry) {
                        if (isset($entry['code']) && $entry['code'] === 200 && isset($entry['body'])) {
                            $allListings[] = (array) $entry['body'];
                        } elseif (! isset($entry['code']) && is_array($entry)) {
                            $allListings[] = $entry;
                        }
                    }
                }
            } else {
                // Si falla el multi-get, recurrir a fetch individual para no perder el lote completo
                foreach ($chunk as $singleId) {
                    try {
                        $allListings[] = $this->fetchListing((string) $singleId);
                    } catch (\Throwable) {
                        // Se omiten ítems individuales con error para registrarlos en el reporte
                    }
                }
            }
        }

        return $allListings;
    }

    /**
     * Busca los IDs de publicaciones del usuario vendedor (/users/{user_id}/items/search).
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function searchListings(string $userId, array $params = []): array
    {
        $defaultParams = [
            'status' => 'active',
            'limit' => 50,
            'offset' => 0,
        ];

        $mergedParams = array_merge($defaultParams, $params);

        $response = $this->authenticatedRequest()
            ->get("{$this->apiBaseUrl}/users/{$userId}/items/search", $mergedParams);

        if ($response->failed()) {
            $this->handleApiError($response, "Error al listar publicaciones del vendedor {$userId}");
        }

        return (array) $response->json();
    }

    /**
     * Busca publicaciones usando el cursor scroll_id para cuentas masivas (12,000+ productos).
     *
     * @return array{results: array<int, string>, scroll_id: ?string, total: int}
     */
    public function searchListingsWithScroll(
        string $userId,
        ?string $scrollId = null,
        int $limit = 50,
        string $status = 'active'
    ): array {
        $params = [
            'status' => $status,
            'search_type' => 'scan',
            'limit' => min(100, max(1, $limit)),
        ];

        if (! empty($scrollId)) {
            $params['scroll_id'] = $scrollId;
        }

        $response = $this->authenticatedRequest()
            ->get("{$this->apiBaseUrl}/users/{$userId}/items/search", $params);

        if ($response->failed()) {
            $this->handleApiError($response, "Error al escanear publicaciones masivas del vendedor {$userId}");
        }

        $data = (array) $response->json();

        return [
            'results' => (array) ($data['results'] ?? []),
            'scroll_id' => $data['scroll_id'] ?? null,
            'total' => (int) ($data['paging']['total'] ?? count($data['results'] ?? [])),
        ];
    }

    /**
     * Actualiza la existencia disponible de una publicación en Mercado Libre.
     */
    public function updateAvailableStock(string $externalId, int $availableQuantity): bool
    {
        $response = $this->authenticatedRequest()
            ->put("{$this->apiBaseUrl}/items/{$externalId}", [
                'available_quantity' => max(0, $availableQuantity),
            ]);

        if ($response->failed()) {
            $this->handleApiError($response, "Error al actualizar existencia de {$externalId} en Mercado Libre");
        }

        return $response->successful();
    }

    /**
     * Consulta el perfil del vendedor autenticado (/users/me o /users/{id}).
     *
     * @return array<string, mixed>
     */
    public function getUserProfile(string $userId = 'me'): array
    {
        $response = $this->authenticatedRequest()->get("{$this->apiBaseUrl}/users/{$userId}");

        if ($response->failed()) {
            $this->handleApiError($response, "Error al consultar perfil de usuario {$userId}");
        }

        return (array) $response->json();
    }

    /**
     * Cliente HTTP preparado con cabecera de autorización Bearer.
     */
    private function authenticatedRequest()
    {
        $accessToken = $this->oauthService->getValidAccessToken();

        if (empty($accessToken)) {
            throw new RuntimeException('No hay una sesión activa de Mercado Libre o el token no pudo ser renovado. Vincula la cuenta en Configuración.');
        }

        return Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(20);
    }

    private function handleApiError(Response $response, string $contextMessage): void
    {
        $status = $response->status();
        $message = $response->json('message') ?? $response->body();

        throw new RuntimeException("{$contextMessage} (HTTP {$status}): {$message}");
    }
}
