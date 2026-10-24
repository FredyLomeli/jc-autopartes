<?php

namespace App\Http\Controllers\Webhooks;

use App\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGateway $paymentGateway,
        private readonly OrderService $orderService,
    ) {}

    /**
     * Procesa notificaciones Webhook / IPN de Mercado Pago.
     * Endpoint: POST /api/webhooks/mercadopago
     */
    public function handle(Request $request): JsonResponse
    {
        $headers = $request->headers->all();
        $rawBody = (string) $request->getContent();

        // 1. Verificación criptográfica de firma (HMAC-SHA256)
        if (! $this->paymentGateway->verifyWebhookSignature($headers, $rawBody)) {
            Log::warning('Webhook Mercado Pago rechazado: firma HMAC inválida', [
                'headers' => $headers,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Firma criptográfica inválida o no autorizada.',
            ], 401);
        }

        // 2. Extraer ID del recurso notificado
        $type = $request->input('type') ?? $request->input('topic') ?? $request->query('topic');
        $dataId = $request->input('data.id')
            ?? $request->input('id')
            ?? $request->query('data.id')
            ?? $request->query('id');

        if (empty($dataId) && ! empty($rawBody)) {
            $json = json_decode($rawBody, true) ?? [];
            $dataId = $json['data']['id'] ?? $json['id'] ?? null;
            $type = $type ?? $json['type'] ?? $json['topic'] ?? null;
        }

        Log::info('Webhook Mercado Pago recibido', [
            'type' => $type,
            'data_id' => $dataId,
        ]);

        if (empty($dataId)) {
            return response()->json([
                'status' => 'acknowledged',
                'message' => 'Notificación sin ID de recurso, omitida.',
            ], 200);
        }

        // 3. Procesar cobros ('payment')
        try {
            $paymentDetails = $this->paymentGateway->getPaymentDetails((string) $dataId);

            $orderNumber = $paymentDetails['external_reference'] ?? null;
            $status = $paymentDetails['status'] ?? null;

            if ($orderNumber && $status === 'approved') {
                try {
                    $order = Order::query()->where('order_number', $orderNumber)->first();

                    if ($order) {
                        $this->orderService->markOrderAsPaid($order, [
                            'payment_id' => $dataId,
                            'payment_type' => $paymentDetails['payment_type_id'] ?? 'mercadopago',
                            'merchant_order_id' => $paymentDetails['merchant_order_id'] ?? null,
                        ]);

                        Log::info("Orden {$orderNumber} acreditada exitosamente vía Webhook MP", [
                            'payment_id' => $dataId,
                        ]);
                    } else {
                        Log::warning("Orden {$orderNumber} no encontrada para acreditar pago MP {$dataId}");
                    }
                } catch (\Throwable $dbEx) {
                    Log::warning('No se pudo consultar orden en base de datos durante webhook', [
                        'error' => $dbEx->getMessage(),
                    ]);
                }
            }
        } catch (Throwable $e) {
            Log::error('Error al procesar evento de webhook Mercado Pago', [
                'error' => $e->getMessage(),
                'data_id' => $dataId,
            ]);

            // Respondemos 200 para evitar que Mercado Pago reintente en bucle si es un error interno no recuperable
            return response()->json([
                'status' => 'error_handled',
                'message' => $e->getMessage(),
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notificación procesada correctamente.',
        ], 200);
    }
}
