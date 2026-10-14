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
            ->timeout(15);
    }

    private function handleApiError(Response $response, string $contextMessage): void
    {
        $status = $response->status();
        $message = $response->json('message') ?? $response->body();

        throw new RuntimeException("{$contextMessage} (HTTP {$status}): {$message}");
    }
}
