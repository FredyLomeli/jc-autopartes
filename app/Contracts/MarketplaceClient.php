<?php

namespace App\Contracts;

/**
 * Contrato base para clientes de marketplace (Mercado Libre en Fases B y E).
 */
interface MarketplaceClient
{
    /**
     * Obtiene el detalle de una publicación remota por su identificador.
     *
     * @return array<string, mixed>
     */
    public function fetchListing(string $externalId): array;

    /**
     * Obtiene en bloque (multi-get) el detalle de múltiples publicaciones.
     *
     * @param  array<int, string>  $externalIds
     * @return array<int, array<string, mixed>>
     */
    public function fetchListings(array $externalIds): array;

    /**
     * Busca las publicaciones asociadas al vendedor en el marketplace.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function searchListings(string $userId, array $params = []): array;

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
    ): array;

    /**
     * Sincroniza la existencia disponible hacia el marketplace.
     */
    public function updateAvailableStock(string $externalId, int $availableQuantity): bool;
}
