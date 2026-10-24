<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class MercadoPagoPaymentGateway implements PaymentGateway
{
    private string $accessToken;
    private string $webhookSecret;
    private string $baseUrl;
    private bool $sandbox;

    public function __construct(
        ?string $accessToken = null,
        ?string $webhookSecret = null,
        ?string $baseUrl = null,
        ?bool $sandbox = null
    ) {
        $this->accessToken = $accessToken ?? (string) config('mercadopago.access_token', '');
        $this->webhookSecret = $webhookSecret ?? (string) config('mercadopago.webhook_secret', '');
        $this->baseUrl = rtrim($baseUrl ?? (string) config('mercadopago.base_url', 'https://api.mercadopago.com'), '/');
        $this->sandbox = $sandbox ?? (bool) config('mercadopago.sandbox', true);
    }

    /**
     * Crea una preferencia de Checkout Pro en Mercado Pago.
     *
     * @param  array<string, mixed>  $orderPayload
     * @return array{
     *     id: string,
     *     init_point: string,
     *     sandbox_init_point: string,
     *     external_reference: string
     * }
     */
    public function createCheckoutPreference(array $orderPayload): array
    {
        $orderNumber = (string) ($orderPayload['order_number'] ?? 'JC-' . time());
        $customerName = (string) ($orderPayload['customer_name'] ?? 'Cliente JC Autopartes');
        $customerEmail = (string) ($orderPayload['customer_email'] ?? 'cliente@jcautopartes.mx');
        $customerPhone = (string) ($orderPayload['customer_phone'] ?? '');

        $rawItems = (array) ($orderPayload['items'] ?? []);
        $items = [];

        foreach ($rawItems as $item) {
            $items[] = [
                'id' => (string) ($item['product_id'] ?? $item['id'] ?? 'ITEM'),
                'title' => (string) ($item['title'] ?? 'Refacción automotriz'),
                'description' => (string) ($item['sku'] ?? 'JC Autopartes'),
                'picture_url' => ! empty($item['photo_url']) ? (string) $item['photo_url'] : null,
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                'currency_id' => 'MXN',
                'unit_price' => round((float) ($item['unit_price'] ?? 0.0), 2),
            ];
        }

        $shippingCost = round((float) ($orderPayload['shipping_cost'] ?? 0.0), 2);

        $backUrls = (array) ($orderPayload['back_urls'] ?? []);
        $successUrl = $backUrls['success'] ?? url('/checkout/exito');
        $failureUrl = $backUrls['failure'] ?? url('/checkout/fallo');
        $pendingUrl = $backUrls['pending'] ?? url('/checkout/pendiente');

        $preferencePayload = [
            'items' => $items,
            'payer' => [
                'name' => $customerName,
                'email' => $customerEmail,
                'phone' => [
                    'number' => $customerPhone,
                ],
            ],
            'back_urls' => [
                'success' => $successUrl,
                'failure' => $failureUrl,
                'pending' => $pendingUrl,
            ],
            'auto_return' => 'approved',
            'external_reference' => $orderNumber,
            'statement_descriptor' => (string) config('mercadopago.statement_descriptor', 'JC AUTOPARTES'),
        ];

        if ($shippingCost > 0) {
            $preferencePayload['shipments'] = [
                'cost' => $shippingCost,
                'mode' => 'not_specified',
            ];
        }

        // Si no hay token configurado (entorno de pruebas o desarrollo local inicial), simular preferencia
        if (empty($this->accessToken)) {
            $mockId = 'sim_pref_' . md5($orderNumber);

            return [
                'id' => $mockId,
                'init_point' => "https://www.mercadopago.com.mx/checkout/v1/redirect?pref_id={$mockId}",
                'sandbox_init_point' => "https://sandbox.mercadopago.com.mx/checkout/v1/redirect?pref_id={$mockId}",
                'external_reference' => $orderNumber,
            ];
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->acceptJson()
                ->post("{$this->baseUrl}/checkout/preferences", $preferencePayload);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'id' => (string) $data['id'],
                    'init_point' => (string) ($data['init_point'] ?? ''),
                    'sandbox_init_point' => (string) ($data['sandbox_init_point'] ?? $data['init_point'] ?? ''),
                    'external_reference' => $orderNumber,
                ];
            }

            Log::error('Error al crear preferencia en Mercado Pago API', [
                'status' => $response->status(),
                'response' => $response->body(),
                'payload' => $preferencePayload,
            ]);

            throw new RuntimeException("Error en Mercado Pago ({$response->status()}): {$response->body()}");
        } catch (Throwable $e) {
            Log::error("Excepción al conectar con Mercado Pago Checkout Pro: {$e->getMessage()}", [
                'order' => $orderNumber,
            ]);

            throw $e;
        }
    }

    /**
     * Verifica la autenticidad y firma criptográfica de un Webhook de Mercado Pago.
     *
     * Algoritmo oficial Mercado Pago:
     * 1. Extraer ts y v1 del encabezado x-signature (ej. ts=1700000000,v1=abc1234...).
     * 2. Extraer x-request-id del encabezado.
     * 3. Extraer data.id de los parámetros o del body JSON.
     * 4. Crear template: "id:[data.id];request-id:[x-request-id];ts:[ts];"
     * 5. Calcular HMAC-SHA256 con el secret de webhook y comparar con v1.
     *
     * @param  array<string, mixed>  $headers
     * @param  string  $rawBody
     */
    public function verifyWebhookSignature(array $headers, string $rawBody): bool
    {
        // En ausencia de secreto configurado o en entorno local simulado, admitir webhook
        if (empty($this->webhookSecret)) {
            return true;
        }

        $xSignature = (string) ($headers['x-signature'][0] ?? $headers['x-signature'] ?? '');
        $xRequestId = (string) ($headers['x-request-id'][0] ?? $headers['x-request-id'] ?? '');

        if (empty($xSignature) || empty($xRequestId)) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $xSignature) as $segment) {
            $pair = explode('=', trim($segment), 2);
            if (count($pair) === 2) {
                $parts[trim($pair[0])] = trim($pair[1]);
            }
        }

        $ts = $parts['ts'] ?? null;
        $v1 = $parts['v1'] ?? null;

        if (! $ts || ! $v1) {
            return false;
        }

        // Obtener data.id del payload
        $dataId = '';
        $decoded = json_decode($rawBody, true);
        if (is_array($decoded) && isset($decoded['data']['id'])) {
            $dataId = (string) $decoded['data']['id'];
        }

        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
        $computed = hash_hmac('sha256', $manifest, $this->webhookSecret);

        return hash_equals($computed, $v1);
    }

    /**
     * Consulta el estado y detalle de un pago en la API de Mercado Pago.
     *
     * @return array<string, mixed>
     */
    public function getPaymentDetails(string|int $paymentId): array
    {
        $id = (string) $paymentId;

        // Modo simulación cuando no hay token configurado
        if (empty($this->accessToken)) {
            return [
                'id' => $id,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'payment_type_id' => 'credit_card',
                'transaction_amount' => 0.0,
                'external_reference' => null,
            ];
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->acceptJson()
                ->get("{$this->baseUrl}/v1/payments/{$id}");

            if ($response->successful()) {
                return (array) $response->json();
            }

            Log::error("Error al consultar pago {$id} en Mercado Pago", [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            throw new RuntimeException("Error consultando pago en Mercado Pago ({$response->status()}): {$response->body()}");
        } catch (Throwable $e) {
            Log::error("Excepción consultando pago {$id}: {$e->getMessage()}");

            throw $e;
        }
    }
}
