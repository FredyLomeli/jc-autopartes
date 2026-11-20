<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmedCustomerMail;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Services\AdminOrderService;
use App\Services\CartService;
use App\Services\OrderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CouponOrderIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_usage_increments_only_when_order_is_paid_and_renders_discount_details(): void
    {
        $this->seed(DatabaseSeeder::class);
        Mail::fake();

        $product = Product::query()->published()->where('stock', '>=', 1)->firstOrFail();
        $product->update(['price' => 2000.00, 'stock' => 5]);

        $coupon = Coupon::create([
            'code' => 'AHORRA200',
            'type' => 'fixed',
            'value' => 200.00,
            'usage_limit' => 1,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $cartService = app(CartService::class);
        $cartService->add($product->id, 1);
        $applyResult = $cartService->applyCoupon('AHORRA200');
        $this->assertTrue($applyResult['ok']);

        $cart = $cartService->getCart();
        $orderService = app(OrderService::class);

        $order = $orderService->createOrderFromCart([
            'customer_name' => 'Ana Torres',
            'customer_email' => 'ana@ejemplo.com',
            'customer_phone' => '3781234567',
            'delivery_method' => Order::DELIVERY_SHIPPING,
            'delivery_variant' => 'ocurre',
            'shipping_postal_code' => '47600',
            'shipping_address' => 'Paquetexpress Sucursal Centro',
            'shipping_neighborhood' => 'Servicio Ocurre',
            'shipping_city' => 'Tepatitlán',
            'shipping_state' => 'Jalisco',
            'shipping_cost' => 150.00,
        ], $cart);

        // El cupón NO debe consumirse mientras la orden esté pending_payment
        $this->assertSame(0, $coupon->fresh()->used_count);
        $this->assertSame(1950.00, $order->total);
        $this->assertSame('Servicio Ocurre (Paquetería)', $order->deliveryMethodLabel());
        $this->assertSame(200.00, $order->discountAmount());
        $this->assertSame('AHORRA200', $order->couponCode());

        // Al acreditar el pago debe incrementar used_count una sola vez (idempotente)
        $orderService->markOrderAsPaid($order, ['payment_id' => 'MP-123']);
        $orderService->markOrderAsPaid($order, ['payment_id' => 'MP-123']);

        $this->assertSame(1, $coupon->fresh()->used_count);

        // AdminOrderService expone el cupón y descuento
        $adminDetails = app(AdminOrderService::class)->getOrderDetails($order->fresh());
        $this->assertSame(200.00, $adminDetails['discount_amount']);
        $this->assertSame('AHORRA200', $adminDetails['coupon_code']);

        // Correo y vista de éxito muestran el descuento
        $mailHtml = (new OrderConfirmedCustomerMail($order->fresh()))->render();
        $this->assertStringContainsString('AHORRA200', $mailHtml);
        $this->assertStringContainsString('-$200.00 MXN', $mailHtml);

        $successResponse = $this->get(route('checkout.success', ['external_reference' => $order->order_number]));
        $successResponse->assertStatus(200);
        $successResponse->assertSee('AHORRA200');
        $successResponse->assertSee('-$200.00 MXN');
    }
}
