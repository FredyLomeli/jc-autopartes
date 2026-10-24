<?php

namespace Tests\Unit;

use App\Contracts\PaymentGateway;
use App\Services\Payments\MercadoPagoPaymentGateway;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoPaymentGatewayTest extends TestCase
{
    public function test_service_container_resolves_payment_gateway_interface(): void
    {
        $gateway = $this->app->make(PaymentGateway::class);
        $this->assertInstanceOf(MercadoPagoPaymentGateway::class, $gateway);
    }

    public function test_create_checkout_preference_in_simulation_mode(): void
    {
        // Sin access token configurado, opera en simulación determinista
        $gateway = new MercadoPagoPaymentGateway(accessToken: '');

        $orderPayload = [
            'order_number' => 'JC-2026-00042',
            'customer_name' => 'María González',
            'customer_email' => 'maria@ejemplo.com',
            'customer_phone' => '3312345678',
            'items' => [
                [
                    'product_id' => 10,
                    'title' => 'Transmisión Automática Versa',
                    'sku' => 'JC-TRA-010',
                    'quantity' => 1,
                    'unit_price' => 14500.0,
                ],
            ],
            'shipping_cost' => 450.0,
        ];

        $preference = $gateway->createCheckoutPreference($orderPayload);

        $this->assertArrayHasKey('id', $preference);
        $this->assertArrayHasKey('init_point', $preference);
        $this->assertArrayHasKey('sandbox_init_point', $preference);
        $this->assertSame('JC-2026-00042', $preference['external_reference']);
        $this->assertStringContainsString('sim_pref_', $preference['id']);
    }

    public function test_create_checkout_preference_with_mocked_http_api(): void
    {
        Http::fake([
            'https://api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => '123456789-abcdef',
                'init_point' => 'https://www.mercadopago.com.mx/checkout/v1/redirect?pref_id=123456789-abcdef',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.mx/checkout/v1/redirect?pref_id=123456789-abcdef',
            ], 201),
        ]);

        $gateway = new MercadoPagoPaymentGateway(
            accessToken: 'TEST-ACCESS-TOKEN-VALIDO',
            baseUrl: 'https://api.mercadopago.com'
        );

        $orderPayload = [
            'order_number' => 'JC-2026-00099',
            'customer_name' => 'Pedro Ramírez',
            'customer_email' => 'pedro@ejemplo.com',
            'customer_phone' => '3781234567',
            'items' => [
                [
                    'product_id' => 5,
                    'title' => 'Alternador Nissan Tiida',
                    'sku' => 'JC-ALT-005',
                    'quantity' => 1,
                    'unit_price' => 2200.0,
                ],
            ],
            'shipping_cost' => 250.0,
        ];

        $preference = $gateway->createCheckoutPreference($orderPayload);

        $this->assertSame('123456789-abcdef', $preference['id']);
        $this->assertSame('JC-2026-00099', $preference['external_reference']);
        $this->assertStringContainsString('pref_id=123456789-abcdef', $preference['init_point']);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://api.mercadopago.com/checkout/preferences'
                && $data['external_reference'] === 'JC-2026-00099'
                && $data['payer']['name'] === 'Pedro Ramírez'
                && count($data['items']) === 1
                && $data['items'][0]['unit_price'] === 2200.0
                && $data['shipments']['cost'] === 250.0;
        });
    }

    public function test_verify_webhook_signature_with_valid_hmac(): void
    {
        $secret = 'super_secret_webhook_key_123';
        $gateway = new MercadoPagoPaymentGateway(webhookSecret: $secret);

        $dataId = '99887766';
        $requestId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $ts = '1700000000';

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $v1 = hash_hmac('sha256', $manifest, $secret);

        $headers = [
            'x-signature' => ["ts={$ts},v1={$v1}"],
            'x-request-id' => [$requestId],
        ];

        $rawBody = json_encode(['data' => ['id' => $dataId]]);

        $isValid = $gateway->verifyWebhookSignature($headers, $rawBody);
        $this->assertTrue($isValid);

        // Con firma alterada debe fallar
        $invalidHeaders = [
            'x-signature' => ["ts={$ts},v1=hash_invalido_totalmente"],
            'x-request-id' => [$requestId],
        ];

        $this->assertFalse($gateway->verifyWebhookSignature($invalidHeaders, $rawBody));
    }

    public function test_get_payment_details_with_mocked_http(): void
    {
        Http::fake([
            'https://api.mercadopago.com/v1/payments/99887766' => Http::response([
                'id' => 99887766,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'payment_type_id' => 'credit_card',
                'external_reference' => 'JC-2026-00099',
                'transaction_amount' => 2450.0,
            ], 200),
        ]);

        $gateway = new MercadoPagoPaymentGateway(
            accessToken: 'TEST-ACCESS-TOKEN-VALIDO',
            baseUrl: 'https://api.mercadopago.com'
        );

        $details = $gateway->getPaymentDetails('99887766');

        $this->assertSame(99887766, $details['id']);
        $this->assertSame('approved', $details['status']);
        $this->assertSame('JC-2026-00099', $details['external_reference']);
        $this->assertEquals(2450.0, $details['transaction_amount']);
    }
}
