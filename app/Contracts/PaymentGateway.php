<?php

namespace App\Contracts;

/**
 * Contrato base para pasarelas de pago (ej. Mercado Pago Checkout Pro en Fase C).
 */
interface PaymentGateway
{
    /**
     * Crea una preferencia o sesión de cobro para una orden.
     *
     * @param  array<string, mixed>  $orderPayload
     * @return array<string, mixed>
     */
    public function createCheckoutPreference(array $orderPayload): array;

    /**
     * Verifica la firma y autenticidad de un webhook entrante.
     *
     * @param  array<string, mixed>  $headers
     * @param  string  $rawBody
     */
    public function verifyWebhookSignature(array $headers, string $rawBody): bool;
}
