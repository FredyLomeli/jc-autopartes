<?php

namespace Tests\Unit;

use App\Mail\OrderConfirmedCustomerMail;
use App\Mail\OrderNotificationAdminMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderService;
use App\Services\PriceService;
use App\Services\StockService;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderEmailRenderingTest extends TestCase
{
    private function createSampleOrder(): Order
    {
        $order = new Order([
            'order_number' => 'JC-2026-00042',
            'customer_name' => 'Alejandro Morales',
            'customer_email' => 'alejandro@ejemplo.com',
            'customer_phone' => '3312345678',
            'delivery_method' => Order::DELIVERY_SHIPPING,
            'shipping_address' => 'Hidalgo 120, Col. Centro',
            'shipping_neighborhood' => 'Centro',
            'shipping_city' => 'Tepatitlán',
            'shipping_state' => 'Jalisco',
            'shipping_postal_code' => '47600',
            'shipping_notes' => 'Dejar en taller mecánico El Güero',
            'items_subtotal' => 2800.0,
            'shipping_cost' => 350.0,
            'total' => 3150.0,
            'currency' => 'MXN',
            'payment_status' => Order::PAYMENT_PAID,
            'order_status' => Order::STATUS_PREPARING,
            'paid_at' => now(),
        ]);

        $item = new OrderItem([
            'product_id' => 105,
            'product_title' => 'Alternador Original Chevrolet Cruze 1.8L',
            'product_sku' => 'JC-ALT-042',
            'part_number' => 'GM-13500582',
            'unit_price' => 2800.0,
            'quantity' => 1,
            'subtotal' => 2800.0,
            'warranty_days' => 15,
        ]);

        $order->setRelation('items', collect([$item]));

        return $order;
    }

    public function test_customer_confirmed_email_renders_expected_html(): void
    {
        $order = $this->createSampleOrder();
        $mailable = new OrderConfirmedCustomerMail($order);

        $rendered = $mailable->render();

        $this->assertStringContainsString('JC-2026-00042', $rendered);
        $this->assertStringContainsString('Alejandro Morales', $rendered);
        $this->assertStringContainsString('Alternador Original Chevrolet Cruze', $rendered);
        $this->assertStringContainsString('JC-ALT-042', $rendered);
        $this->assertStringContainsString('GM-13500582', $rendered);
        $this->assertStringContainsString('3,150.00', $rendered);
        $this->assertStringContainsString('Garantía: 15 días', $rendered);
        $this->assertStringContainsString('Hidalgo 120', $rendered);
        $this->assertStringContainsString('Dejar en taller mecánico El Güero', $rendered);
        $this->assertStringContainsString('¿Requieres Factura Fiscal?', $rendered);

        $envelope = $mailable->envelope();
        $this->assertStringContainsString('JC-2026-00042', $envelope->subject);
    }

    public function test_admin_notification_email_renders_expected_html(): void
    {
        $order = $this->createSampleOrder();
        $mailable = new OrderNotificationAdminMail($order);

        $rendered = $mailable->render();

        $this->assertStringContainsString('JC-2026-00042', $rendered);
        $this->assertStringContainsString('Alejandro Morales', $rendered);
        $this->assertStringContainsString('3312345678', $rendered);
        $this->assertStringContainsString('Alternador Original Chevrolet Cruze', $rendered);
        $this->assertStringContainsString('JC-ALT-042', $rendered);
        $this->assertStringContainsString('Acción requerida en almacén', $rendered);
        $this->assertStringContainsString('Hidalgo 120', $rendered);

        $envelope = $mailable->envelope();
        $this->assertStringContainsString('JC-2026-00042', $envelope->subject);
    }

    public function test_order_service_sends_customer_and_admin_emails(): void
    {
        Mail::fake();

        $order = $this->createSampleOrder();

        $stockService = $this->createMock(StockService::class);
        $priceService = new PriceService();
        $orderService = new OrderService($stockService, $priceService);

        $orderService->sendOrderConfirmedEmails($order);

        Mail::assertSent(OrderConfirmedCustomerMail::class, function (OrderConfirmedCustomerMail $mail) use ($order) {
            return $mail->order->order_number === $order->order_number
                && $mail->hasTo('alejandro@ejemplo.com');
        });

        Mail::assertSent(OrderNotificationAdminMail::class, function (OrderNotificationAdminMail $mail) use ($order) {
            return $mail->order->order_number === $order->order_number
                && $mail->hasTo(config('business.email'));
        });
    }
}
