<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Promotion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPromotionsAndCheckoutStepperTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_shows_promotion_badge_and_strike_price_for_discounted_products(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Crear una promoción activa para la categoría Motores con 15% de descuento
        Promotion::create([
            'name' => 'Especial Motores',
            'badge_label' => 'OFERTA',
            'mode' => 'direct',
            'visible_discount_percentage' => 15.00,
            'real_discount_percentage' => 15.00,
            'scope_type' => 'category',
            'scope_value' => 'Motores',
            'is_active' => true,
        ]);

        $motorProduct = Product::query()->where('category', 'Motores')->firstOrFail();

        $response = $this->get(route('catalog.index'));
        $response->assertStatus(200);
        $response->assertSee('OFERTA');

        // Ficha del producto
        $showResponse = $this->get(route('catalog.show', $motorProduct->slug));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('OFERTA');
        $showResponse->assertSee('¡Ahorras');
    }

    public function test_catalog_filters_by_active_offers(): void
    {
        $this->seed(DatabaseSeeder::class);

        Promotion::create([
            'name' => 'Promo Transmisiones',
            'badge_label' => '-20% OFF',
            'mode' => 'direct',
            'visible_discount_percentage' => 20.00,
            'real_discount_percentage' => 20.00,
            'scope_type' => 'category',
            'scope_value' => 'Transmisiones',
            'is_active' => true,
        ]);

        $response = $this->get(route('catalog.index', ['ofertas' => '1']));
        $response->assertStatus(200);
        $response->assertSee('Campañas de Oferta y Promociones Activas');
    }

    public function test_cart_can_apply_and_remove_valid_coupon(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::query()->published()->where('price', '>', 5000)->firstOrFail();

        Coupon::create([
            'code' => 'DESCUENTO10',
            'type' => 'percentage',
            'value' => 10.00,
            'min_order_amount' => 1000.00,
            'is_active' => true,
        ]);

        // 1. Agregar producto al carrito
        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // 2. Aplicar cupón válido
        $applyResponse = $this->post(route('cart.coupon.apply'), [
            'coupon_code' => 'descuento10',
        ]);
        $applyResponse->assertSessionHas('success');

        // 3. Revisar carrito
        $cartResponse = $this->get(route('cart.index'));
        $cartResponse->assertStatus(200);
        $cartResponse->assertSee('DESCUENTO10');
        $cartResponse->assertSee('Cupón (DESCUENTO10)');

        // 4. Remover cupón
        $removeResponse = $this->delete(route('cart.coupon.remove'));
        $removeResponse->assertSessionHas('success');
    }

    public function test_cart_rejects_invalid_or_expired_coupon(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::query()->published()->firstOrFail();

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->post(route('cart.coupon.apply'), [
            'coupon_code' => 'CODIGO_INEXISTENTE',
        ]);

        $response->assertSessionHas('error');
    }

    public function test_checkout_page_renders_stepper_and_order_summary(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::query()->published()->firstOrFail();

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->get(route('checkout.index'));
        $response->assertStatus(200);
        $response->assertSee('1. Entrega');
        $response->assertSee('2. Datos');
        $response->assertSee('3. Pago');
        $response->assertSee('Envío a Domicilio');
        $response->assertSee('Servicio Ocurre');
        $response->assertSee('Recoger en Mostrador');
        $response->assertSee('Mercado Pago');
    }
}
