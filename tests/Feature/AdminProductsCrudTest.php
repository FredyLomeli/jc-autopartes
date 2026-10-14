<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\PriceService;
use App\Services\StockService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminProductsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::query()->where('email', 'admin@jcautopartes.mx')->firstOrFail();
    }

    public function test_admin_can_list_and_filter_products_in_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.products.index', [
            'category' => 'Motores',
            'status' => 'published',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Products/Index')
            ->where('filters.category', 'Motores')
            ->where('filters.status', 'published')
            ->has('products.data')
        );
    }

    public function test_admin_can_create_published_product_and_it_syncs_immediately_to_public_catalog(): void
    {
        $payload = [
            'sku' => 'JC-MOT-999',
            'part_number' => 'PY-01-02-100',
            'oem_number' => 'PY0102100A',
            'side' => 'izquierdo',
            'position' => 'delantero',
            'mercadolibre_id' => 'MLM999888777',
            'mercadolibre_permalink' => 'https://articulo.mercadolibre.com.mx/MLM-999888777-motor-mazda.html',
            'title' => 'Motor 2.5L SkyActiv-G Mazda 3 2019-2022 Original',
            'category' => 'Motores',
            'vehicle_brand' => 'Mazda',
            'vehicle_model' => 'Mazda 3',
            'year_start' => 2019,
            'year_end' => 2022,
            'condition' => 'A',
            'price' => 34500.00,
            'stock' => 2,
            'description' => 'Motor verificado con compresión pareja y garantía de 30 días.',
            'status' => 'published',
            'is_featured' => true,
            'photo_urls' => [
                'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?auto=format&fit=crop&w=900&q=80',
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.products.store'), $payload);

        $response->assertRedirect(route('admin.products.index'));

        $product = Product::query()->where('sku', 'JC-MOT-999')->firstOrFail();

        $priceService = app(PriceService::class);
        $stockService = app(StockService::class);

        $this->assertSame(34500.00, $priceService->getUnitPrice($product));
        $this->assertSame(2, $stockService->getAvailableStock($product));
        $this->assertSame('PY-01-02-100', $product->part_number);
        $this->assertSame('PY0102100A', $product->oem_number);
        $this->assertSame('izquierdo', $product->side);
        $this->assertSame('delantero', $product->position);
        $this->assertSame('MLM999888777', $product->mercadolibre_id);
        $this->assertTrue($product->isMercadoLibreSynced());
        $this->assertCount(1, $product->photos);

        // Verificar que aparece inmediatamente en el catálogo público y en su ficha de detalle
        $this->get(route('catalog.index', ['q' => 'JC-MOT-999']))
            ->assertStatus(200)
            ->assertSee('Motor 2.5L SkyActiv-G Mazda 3 2019-2022 Original')
            ->assertSee('$34,500.00 MXN');

        $this->get(route('catalog.show', $product->slug))
            ->assertStatus(200)
            ->assertSee('Motor 2.5L SkyActiv-G Mazda 3 2019-2022 Original')
            ->assertSee('PY-01-02-100')
            ->assertSee('30 días');
    }

    public function test_admin_can_update_product_price_stock_and_status_syncing_with_public_store(): void
    {
        $product = Product::query()->published()->firstOrFail();

        $updatePayload = [
            'sku' => $product->sku,
            'title' => $product->title . ' Editado Admin',
            'slug' => $product->slug,
            'category' => $product->category,
            'vehicle_brand' => $product->vehicle_brand,
            'vehicle_model' => $product->vehicle_model,
            'year_start' => $product->year_start,
            'year_end' => $product->year_end,
            'condition' => $product->condition,
            'price' => 19850.50,
            'stock' => 4,
            'description' => 'Descripción actualizada desde el panel.',
            'status' => 'published',
            'is_featured' => false,
            'photo_urls' => [$product->primary_photo_url],
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), $updatePayload)
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertSame(19850.50, app(PriceService::class)->getUnitPrice($product));
        $this->assertSame(4, app(StockService::class)->getAvailableStock($product));

        // Verificar en ficha pública el título y precio actualizados
        $this->get(route('catalog.show', $product->slug))
            ->assertStatus(200)
            ->assertSee('Editado Admin')
            ->assertSee('$19,850.50 MXN');

        // Cambiar a borrador (draft) y verificar que se oculta en tienda pública (404)
        $updatePayload['status'] = 'draft';
        $this->actingAs($this->admin)
            ->put(route('admin.products.update', $product), $updatePayload)
            ->assertRedirect(route('admin.products.index'));

        $this->get(route('catalog.show', $product->slug))
            ->assertStatus(404);
    }

    public function test_admin_can_delete_product(): void
    {
        $product = Product::query()->firstOrFail();
        $productId = $product->id;

        $this->actingAs($this->admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('products', ['id' => $productId]);
    }
}
