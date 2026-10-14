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
     * Busca las publicaciones asociadas al vendedor en el marketplace.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function searchListings(string $userId, array $params = []): array;

    /**
     * Sincroniza la existencia disponible hacia el marketplace.
     */
    public function updateAvailableStock(string $externalId, int $availableQuantity): bool;
}
