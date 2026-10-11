<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\StockService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartAndCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_cart_persists_items_across_pages_and_calculates_totals_via_price_service(): void
    {
        $product = Product::query()->where('sku', 'JC-NIS-001')->firstOrFail(); // $18,500.00, stock 2

        $postResponse = $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $postResponse->assertRedirect(route('cart.index'));

        // Verificar en /carrito
        $cartResponse = $this->get(route('cart.index'));
        $cartResponse->assertStatus(200);
        $this->assertSame(1, substr_count($cartResponse->getContent(), '<h1'));
        $cartResponse->assertSee($product->title, false);
        $cartResponse->assertSee('$18,500.00 MXN', false);

        // Verificar que la sesión se mantiene al navegar a otra página (/refacciones)
        $catalogResponse = $this->get(route('catalog.index'));
        $catalogResponse->assertStatus(200);
        $this->assertSame(1, session('cart.items')[$product->id]['quantity'] ?? 0);
    }

    public function test_cart_does_not_allow_quantities_greater_than_available_stock(): void
    {
        $product = Product::query()->where('sku', 'JC-NIS-001')->firstOrFail(); // stock = 2

        // Intentar agregar 5 cuando solo hay 2 en existencia
        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 5,
        ])->assertRedirect(route('cart.index'));

        $this->assertSame(2, session('cart.items')[$product->id]['quantity']);

        // Intentar actualizar a 10 desde el carrito
        $this->patch(route('cart.update', $product->id), [
            'quantity' => 10,
        ])->assertRedirect(route('cart.index'));

        $this->assertSame(2, session('cart.items')[$product->id]['quantity']);

        $cartResponse = $this->get(route('cart.index'));
        $cartResponse->assertSee('$37,000.00 MXN', false); // 2 * $18,500.00
    }

    public function test_cart_rejects_draft_or_out_of_stock_products(): void
    {
        $draft = Product::query()->where('sku', 'JC-VW-006')->firstOrFail();

        $this->post(route('cart.add'), [
            'product_id' => $draft->id,
            'quantity' => 1,
        ]);

        $this->assertEmpty(session('cart.items', []));

        $outOfStock = Product::query()->where('sku', 'JC-NIS-002')->firstOrFail();
        app(StockService::class)->setStock($outOfStock, 0);

        $this->post(route('cart.add'), [
            'product_id' => $outOfStock->id,
            'quantity' => 1,
        ]);

        $this->assertEmpty(session('cart.items', []));
    }

    public function test_cart_can_remove_item_and_clear_all(): void
    {
        $p1 = Product::query()->where('sku', 'JC-NIS-001')->firstOrFail();
        $p2 = Product::query()->where('sku', 'JC-TOY-001')->firstOrFail();

        $this->post(route('cart.add'), ['product_id' => $p1->id, 'quantity' => 1]);
        $this->post(route('cart.add'), ['product_id' => $p2->id, 'quantity' => 1]);

        $this->assertCount(2, session('cart.items', []));

        $this->delete(route('cart.remove', $p1->id))->assertRedirect(route('cart.index'));
        $this->assertCount(1, session('cart.items', []));

        $this->delete(route('cart.clear'))->assertRedirect(route('cart.index'));
        $this->assertEmpty(session('cart.items', []));
    }

    public function test_checkout_redirects_when_empty_and_renders_visual_checkout_with_disabled_button_when_populated(): void
    {
        // Carrito vacío redirige a /carrito
        $this->get(route('checkout.index'))->assertRedirect(route('cart.index'));

        // Con artículo en el carrito carga el checkout visual
        $product = Product::query()->where('sku', 'JC-NIS-001')->firstOrFail();
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);

        $response = $this->get(route('checkout.index'));
        $response->assertStatus(200);
        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
        $response->assertSee($product->title, false);
        $response->assertSee('$37,000.00 MXN', false);
        $response->assertSee('name="postal_code"', false);
        $response->assertSee('Para factura, escríbenos por WhatsApp', false);
        $response->assertSee('Disponible en la siguiente etapa', false);
    }
}
