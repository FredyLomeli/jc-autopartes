<?php

namespace App\Contracts;

/**
 * Contrato base para mensajería oficial de WhatsApp (Meta Cloud API en Fase F).
 */
interface WhatsAppChannel
{
    /**
     * Envía un mensaje de texto libre dentro de la ventana de atención de 24h.
     */
    public function sendTextMessage(string $recipientPhone, string $message): bool;

    /**
     * Envía una plantilla aprobada por Meta.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function sendTemplateMessage(string $recipientPhone, string $templateName, array $parameters = []): bool;
}
