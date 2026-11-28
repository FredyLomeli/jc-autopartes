<?php

namespace Tests\Feature;

use App\Contracts\MarketplaceClient;
use App\Models\MarketplaceRawItem;
use App\Models\Product;
use App\Models\User;
use App\Services\MercadoLibre\MercadoLibreCatalogSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProductDimensionsAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_audit_routes_require_authentication(): void
    {
        $this->get(route('admin.products.dimensions-audit'))->assertRedirect(route('login'));
        $this->get(route('admin.products.dimensions-audit.export'))->assertRedirect(route('login'));
    }

    public function test_audit_page_identifies_incomplete_and_low_weight_motors(): void
    {
        Product::query()->create([
            'sku' => 'MOT-SUSP-1',
            'title' => 'Motor 3/4 Ford Lobo',
            'slug' => 'motor-3-4-ford-lobo',
            'category' => 'Motores',
            'vehicle_brand' => 'Ford',
            'vehicle_model' => 'Lobo',
            'year_start' => 2010,
            'year_end' => 2014,
            'condition' => 'A',
            'price' => 25000,
            'stock' => 1,
            'weight_kg' => 3.29,
            'height_cm' => 39,
            'width_cm' => 17,
            'length_cm' => 11,
            'status' => 'published',
            'dimensions_locked' => false,
            'dimensions_source_incomplete' => false,
        ]);

        Product::query()->create([
            'sku' => 'FAR-INC-1',
            'title' => 'Faro Jetta Completo',
            'slug' => 'faro-jetta-completo',
            'category' => 'Iluminación',
            'vehicle_brand' => 'Volkswagen',
            'vehicle_model' => 'Jetta',
            'year_start' => 2015,
            'year_end' => 2018,
            'condition' => 'A',
            'price' => 2500,
            'stock' => 1,
            'weight_kg' => 6.0,
            'height_cm' => 25,
            'width_cm' => 30,
            'length_cm' => 60,
            'status' => 'published',
            'dimensions_locked' => false,
            'dimensions_source_incomplete' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.products.dimensions-audit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Products/DimensionsAudit')
            ->where('stats.motors_suspect_count', 1)
            ->where('stats.incomplete_count', 1)
            ->where('stats.suspect_count', 2)
            ->has('items.data', 2)
        );
    }

    public function test_inline_dimensions_update_saves_and_locks_product_against_meli_overwrite(): void
    {
        $product = Product::query()->create([
            'mercadolibre_id' => 'MLM999111222',
            'sku' => 'MOT-LOCK-1',
            'title' => 'Motor 3/4 Focus',
            'slug' => 'motor-3-4-focus',
            'category' => 'Motores',
            'vehicle_brand' => 'Ford',
            'vehicle_model' => 'Focus',
            'year_start' => 2012,
            'year_end' => 2018,
            'condition' => 'A',
            'price' => 18000,
            'stock' => 1,
            'weight_kg' => 11.48,
            'height_cm' => 49,
            'width_cm' => 44,
            'length_cm' => 43,
            'status' => 'published',
            'dimensions_locked' => false,
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.products.dimensions.update', $product), [
                'weight_kg' => 145.5,
                'length_cm' => 95,
                'width_cm' => 75,
                'height_cm' => 80,
            ])
            ->assertRedirect();

        $product->refresh();
        $this->assertTrue($product->dimensions_locked);
        $this->assertSame('145.50', (string) $product->weight_kg);
        $this->assertSame('95.00', (string) $product->length_cm);

        MarketplaceRawItem::query()->create([
            'marketplace' => 'mercadolibre',
            'external_id' => 'MLM999111222',
            'title' => 'Motor 3/4 Focus',
            'status' => 'active',
            'raw_payload' => [
                'id' => 'MLM999111222',
                'title' => 'Motor 3/4 Focus Actualizado',
                'price' => 19500,
                'available_quantity' => 2,
                'status' => 'active',
                'category_id' => 'MLM169191',
                'attributes' => [
                    ['id' => 'BRAND', 'value_name' => 'Ford'],
                    ['id' => 'MODEL', 'value_name' => 'Focus'],
                    ['id' => 'VEHICLE_YEAR', 'value_name' => '2012-2018'],
                    ['id' => 'SELLER_PACKAGE_WEIGHT', 'value_name' => '11480 g'],
                    ['id' => 'SELLER_PACKAGE_HEIGHT', 'value_name' => '49 cm'],
                    ['id' => 'SELLER_PACKAGE_WIDTH', 'value_name' => '44 cm'],
                    ['id' => 'SELLER_PACKAGE_LENGTH', 'value_name' => '43 cm'],
                ],
                'pictures' => [],
            ],
            'fetched_at' => now(),
        ]);

        app()->instance(MarketplaceClient::class, Mockery::mock(MarketplaceClient::class));
        app(MercadoLibreCatalogSyncService::class)->syncFromRawChunked();

        $product->refresh();
        // Precio y stock sí actualizan desde ML, pero peso y medidas bloqueados permanecen protegidos
        $this->assertSame('19500.00', (string) $product->price);
        $this->assertSame(2, $product->stock);
        $this->assertSame('145.50', (string) $product->weight_kg);
        $this->assertSame('95.00', (string) $product->length_cm);
        $this->assertSame('75.00', (string) $product->width_cm);
        $this->assertSame('80.00', (string) $product->height_cm);
    }

    public function test_csv_export_returns_downloadable_csv(): void
    {
        Product::query()->create([
            'sku' => 'CSV-MOT-1',
            'title' => 'Motor Prueba CSV',
            'slug' => 'motor-prueba-csv',
            'category' => 'Motores',
            'vehicle_brand' => 'Nissan',
            'vehicle_model' => 'Versa',
            'year_start' => 2016,
            'year_end' => 2020,
            'condition' => 'A',
            'price' => 15000,
            'stock' => 1,
            'weight_kg' => 5.0,
            'height_cm' => 30,
            'width_cm' => 30,
            'length_cm' => 30,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.products.dimensions-audit.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('CSV-MOT-1', $content);
        $this->assertStringContainsString('Motor Prueba CSV', $content);
    }
}
