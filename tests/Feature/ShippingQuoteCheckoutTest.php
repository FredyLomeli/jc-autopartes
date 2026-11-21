<?php

namespace Tests\Feature;

use App\Contracts\ShippingQuoteProvider;
use App\Models\Order;
use App\Models\Product;
use App\Services\Shipping\SimulatedPaquetexpressQuoteProvider;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingQuoteCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulated_paquetexpress_provider_quotes_by_zone_and_variant(): void
    {
        $provider = new SimulatedPaquetexpressQuoteProvider();
        $packages = [
            ['quantity' => 1, 'weight_kg' => 5.0, 'length_cm' => 30, 'width_cm' => 30, 'height_cm' => 20],
        ];

        $invalid = $provider->quote('123', $packages, 'domicilio');
        $this->assertFalse($invalid['valid']);

        $localDomicilio = $provider->quote('47600', $packages, 'domicilio');
        $localOcurre = $provider->quote('47600', $packages, 'ocurre');
        $nationalDomicilio = $provider->quote('64000', $packages, 'domicilio');
        $pickup = $provider->quote('47600', $packages, 'pickup');

        $this->assertTrue($localDomicilio['valid']);
        $this->assertTrue($localOcurre['valid']);
        $this->assertLessThan($localDomicilio['cost'], $localOcurre['cost']);
        $this->assertGreaterThan($localDomicilio['cost'], $nationalDomicilio['cost']);
        $this->assertSame(0.0, $pickup['cost']);
    }

    public function test_checkout_quote_shipping_endpoint_validates_and_returns_quote(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::query()->published()->where('stock', '>=', 1)->firstOrFail();
        $product->update(['price' => 5000.00, 'stock' => 3, 'weight_kg' => 4.0]);

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        $invalidResp = $this->postJson(route('checkout.quote-shipping'), [
            'postal_code' => '47',
            'delivery_variant' => 'domicilio',
        ]);
        $invalidResp->assertStatus(422);

        $domicilioResp = $this->postJson(route('checkout.quote-shipping'), [
            'postal_code' => '44100',
            'delivery_variant' => 'domicilio',
        ]);
        $domicilioResp->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('quote.postal_code', '44100')
            ->assertJsonPath('quote.delivery_variant', 'domicilio')
            ->assertJsonPath('quote.carrier', 'Paquetexpress');

        $ocurreResp = $this->postJson(route('checkout.quote-shipping'), [
            'postal_code' => '44100',
            'delivery_variant' => 'ocurre',
        ]);
        $ocurreResp->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('quote.delivery_variant', 'ocurre');

        $this->assertLessThan(
            $domicilioResp->json('quote.cost'),
            $ocurreResp->json('quote.cost')
        );
    }

    public function test_checkout_store_recalculates_shipping_cost_server_side_for_ocurre(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::query()->published()->where('stock', '>=', 1)->firstOrFail();
        $product->update(['price' => 4000.00, 'stock' => 2, 'weight_kg' => 5.0]);

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        $expectedQuote = app(ShippingQuoteProvider::class)->quote('44100', [
            [
                'quantity' => 1,
                'weight_kg' => 5.0,
                'length_cm' => (float) ($product->length_cm ?? 35.0),
                'width_cm' => (float) ($product->width_cm ?? 30.0),
                'height_cm' => (float) ($product->height_cm ?? 25.0),
            ],
        ], 'ocurre');

        $response = $this->post(route('checkout.store'), [
            'customer_name' => 'Luis Ramírez',
            'customer_phone' => '3312345678',
            'customer_email' => 'luis@ejemplo.com',
            'delivery_method' => Order::DELIVERY_SHIPPING,
            'delivery_variant' => 'ocurre',
            'shipping_postal_code' => '44100',
            'shipping_address' => 'Paquetexpress Sucursal Guadalajara Centro',
            'shipping_neighborhood' => 'Servicio Ocurre',
            'shipping_city' => 'Guadalajara',
            'shipping_state' => 'Jalisco',
            'shipping_cost' => 1.00, // intento de manipulación desde cliente debe ser ignorado
        ]);

        $response->assertStatus(302);

        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertSame((float) $expectedQuote['cost'], $order->shipping_cost);
        $this->assertSame(round(4000.00 + $expectedQuote['cost'], 2), $order->total);
        $this->assertSame('ocurre', $order->deliveryVariant());
        $this->assertSame('Servicio Ocurre (Paquetería)', $order->deliveryMethodLabel());
    }

    public function test_checkout_renders_postal_code_quote_block_in_step_one(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::query()->published()->where('stock', '>=', 1)->firstOrFail();
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        $response = $this->get(route('checkout.index'));
        $response->assertStatus(200);
        $response->assertSee('step1-postal-code', false);
        $response->assertSee('Validar C.P. y Cotizar', false);
        $response->assertSee('name="delivery_variant"', false);
    }
}
