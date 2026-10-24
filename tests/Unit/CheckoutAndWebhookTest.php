<?php

namespace Tests\Unit;

use App\Contracts\PaymentGateway;
use App\Http\Controllers\Public\CheckoutController;
use App\Http\Controllers\Webhooks\MercadoPagoWebhookController;
use App\Http\Requests\Public\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CheckoutAndWebhookTest extends TestCase
{
    public function test_store_order_request_validates_required_buyer_data(): void
    {
        $request = new StoreOrderRequest();
        $rules = $request->rules();

        $v1 = Validator::make([], $rules);
        $this->assertTrue($v1->fails());
        $this->assertArrayHasKey('customer_name', $v1->errors()->toArray());
        $this->assertArrayHasKey('customer_phone', $v1->errors()->toArray());
        $this->assertArrayHasKey('customer_email', $v1->errors()->toArray());
        $this->assertArrayHasKey('delivery_method', $v1->errors()->toArray());

        // Válido para recolección en mostrador
        $validPickupData = [
            'customer_name' => 'Roberto Sánchez',
            'customer_phone' => '3781234567',
            'customer_email' => 'roberto@ejemplo.com',
            'delivery_method' => 'pickup',
        ];
        $v2 = Validator::make($validPickupData, $rules);
        $this->assertFalse($v2->fails());
    }

    public function test_store_order_request_requires_5_digit_postal_code_and_address_for_shipping(): void
    {
        $request = new StoreOrderRequest();
        $rules = $request->rules();

        $invalidShippingData = [
            'customer_name' => 'Roberto Sánchez',
            'customer_phone' => '3781234567',
            'customer_email' => 'roberto@ejemplo.com',
            'delivery_method' => 'shipping',
            'shipping_postal_code' => '476', // menos de 5 dígitos
        ];
        $v1 = Validator::make($invalidShippingData, $rules);
        $this->assertTrue($v1->fails());
        $this->assertArrayHasKey('shipping_postal_code', $v1->errors()->toArray());
        $this->assertArrayHasKey('shipping_address', $v1->errors()->toArray());

        // Con 5 dígitos y dirección completa
        $validShippingData = [
            'customer_name' => 'Roberto Sánchez',
            'customer_phone' => '3781234567',
            'customer_email' => 'roberto@ejemplo.com',
            'delivery_method' => 'shipping',
            'shipping_postal_code' => '47600',
            'shipping_address' => 'Av. Manuel Gómez Morín 450',
            'shipping_neighborhood' => 'Centro',
            'shipping_city' => 'Tepatitlán de Morelos',
            'shipping_state' => 'Jalisco',
        ];
        $v2 = Validator::make($validShippingData, $rules);
        $this->assertFalse($v2->fails());
    }

    public function test_checkout_controller_store_redirects_to_mercadopago_init_point(): void
    {
        $cartService = $this->createMock(CartService::class);
        $cartService->method('getCart')->willReturn([
            'items' => [
                [
                    'product_id' => 1,
                    'title' => 'Transmisión Estándar Nissan Sentra',
                    'sku' => 'JC-TRA-001',
                    'quantity' => 1,
                    'unit_price' => 12500.0,
                    'line_subtotal' => 12500.0,
                ],
            ],
            'totalQuantity' => 1,
            'subtotal' => 12500.0,
        ]);

        $mockOrder = new Order([
            'order_number' => 'JC-2026-00042',
            'customer_name' => 'Carlos Mendoza',
            'customer_email' => 'carlos@ejemplo.com',
            'customer_phone' => '3312345678',
            'delivery_method' => 'pickup',
            'total' => 12500.0,
        ]);
        $mockOrder->id = 42;

        $orderService = $this->createMock(OrderService::class);
        $orderService->expects($this->once())
            ->method('createOrderFromCart')
            ->willReturn($mockOrder);

        $paymentGateway = $this->createMock(PaymentGateway::class);
        $paymentGateway->expects($this->once())
            ->method('createCheckoutPreference')
            ->willReturn([
                'id' => 'pref_123456',
                'init_point' => 'https://www.mercadopago.com.mx/checkout/v1/redirect?pref_id=pref_123456',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.mx/checkout/v1/redirect?pref_id=pref_123456',
                'external_reference' => 'JC-2026-00042',
            ]);

        $controller = new CheckoutController($cartService, $orderService, $paymentGateway);

        $request = StoreOrderRequest::create('/checkout', 'POST', [
            'customer_name' => 'Carlos Mendoza',
            'customer_email' => 'carlos@ejemplo.com',
            'customer_phone' => '3312345678',
            'delivery_method' => 'pickup',
        ]);

        $response = $controller->store($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('mercadopago.com', $response->getTargetUrl());
        $this->assertStringContainsString('pref_123456', $response->getTargetUrl());
    }

    public function test_webhook_controller_rejects_unauthorized_signature(): void
    {
        $paymentGateway = $this->createMock(PaymentGateway::class);
        $paymentGateway->expects($this->once())
            ->method('verifyWebhookSignature')
            ->willReturn(false);

        $orderService = $this->createMock(OrderService::class);

        $controller = new MercadoPagoWebhookController($paymentGateway, $orderService);

        $request = Request::create('/api/webhooks/mercadopago', 'POST', [], [], [], [], json_encode([
            'type' => 'payment',
            'data' => ['id' => '123456789'],
        ]));

        $response = $controller->handle($request);

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('error', $data['status']);
    }

    public function test_webhook_controller_handles_approved_payment(): void
    {
        $paymentGateway = $this->createMock(PaymentGateway::class);
        $paymentGateway->expects($this->once())
            ->method('verifyWebhookSignature')
            ->willReturn(true);

        $paymentGateway->expects($this->once())
            ->method('getPaymentDetails')
            ->with('999888777')
            ->willReturn([
                'id' => 999888777,
                'status' => 'approved',
                'external_reference' => 'JC-2026-99999',
                'payment_type_id' => 'credit_card',
                'merchant_order_id' => 'MO-12345',
            ]);

        $orderService = $this->createMock(OrderService::class);

        $controller = new MercadoPagoWebhookController($paymentGateway, $orderService);

        $request = Request::create('/api/webhooks/mercadopago', 'POST', [], [], [], [], json_encode([
            'type' => 'payment',
            'data' => ['id' => '999888777'],
        ]));

        $response = $controller->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('success', $data['status']);
    }

    public function test_order_to_payment_payload_structure(): void
    {
        $order = new Order([
            'order_number' => 'JC-2026-00001',
            'customer_name' => 'María González',
            'customer_email' => 'maria@ejemplo.com',
            'customer_phone' => '3311223344',
            'shipping_cost' => 350.0,
            'total' => 3850.0,
        ]);

        // Simular items cargados en relación
        $item = new OrderItem([
            'product_id' => 10,
            'product_title' => 'Amortiguador Delantero Chevrolet Aveo',
            'product_sku' => 'JC-AMO-001',
            'unit_price' => 3500.0,
            'quantity' => 1,
            'subtotal' => 3500.0,
        ]);
        $order->setRelation('items', collect([$item]));

        $payload = $order->toPaymentPayload();

        $this->assertEquals('JC-2026-00001', $payload['order_number']);
        $this->assertEquals('María González', $payload['customer_name']);
        $this->assertEquals(350.0, $payload['shipping_cost']);
        $this->assertCount(1, $payload['items']);
        $this->assertEquals('Amortiguador Delantero Chevrolet Aveo', $payload['items'][0]['title']);
        $this->assertArrayHasKey('success', $payload['back_urls']);
        $this->assertArrayHasKey('failure', $payload['back_urls']);
        $this->assertArrayHasKey('pending', $payload['back_urls']);
    }

    public function test_checkout_index_redirects_if_cart_is_empty(): void
    {
        $cartService = $this->createMock(CartService::class);
        $cartService->method('getCart')->willReturn([
            'items' => [],
            'totalQuantity' => 0,
            'subtotal' => 0.0,
        ]);

        $orderService = $this->createMock(OrderService::class);
        $paymentGateway = $this->createMock(PaymentGateway::class);

        $controller = new CheckoutController($cartService, $orderService, $paymentGateway);

        $response = $controller->index();
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals(route('cart.index'), $response->getTargetUrl());
    }

    public function test_checkout_store_redirects_to_cart_if_stock_insufficient(): void
    {
        $cartService = $this->createMock(CartService::class);
        $cartService->method('getCart')->willReturn([
            'items' => [
                ['product_id' => 99, 'title' => 'Pieza Agotada', 'quantity' => 1],
            ],
            'totalQuantity' => 1,
            'subtotal' => 1500.0,
        ]);

        $orderService = $this->createMock(OrderService::class);
        $orderService->expects($this->once())
            ->method('createOrderFromCart')
            ->willThrowException(new \App\Exceptions\InsufficientStockException('Existencia insuficiente'));

        $paymentGateway = $this->createMock(PaymentGateway::class);

        $controller = new CheckoutController($cartService, $orderService, $paymentGateway);

        $request = StoreOrderRequest::create('/checkout', 'POST', [
            'customer_name' => 'Luis López',
            'customer_email' => 'luis@ejemplo.com',
            'customer_phone' => '3312345678',
            'delivery_method' => 'pickup',
        ]);

        $response = $controller->store($request);
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals(route('cart.index'), $response->getTargetUrl());
    }
}

