<?php

namespace App\Contracts;

/**
 * Contrato base para cotizadores de paquetería (ej. Paquetexpress en Fase D).
 */
interface ShippingQuoteProvider
{
    /**
     * Cotiza el envío a un código postal destino según peso y dimensiones.
     *
     * @param  string  $destinationPostalCode
     * @param  array<int, array<string, float|int>>  $packages
     * @return array<string, mixed>
     */
    public function quote(string $destinationPostalCode, array $packages): array;
}
