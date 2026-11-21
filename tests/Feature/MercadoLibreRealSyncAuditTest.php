<?php

namespace Tests\Feature;

use App\Contracts\MarketplaceClient;
use App\Models\MarketplaceToken;
use App\Models\Product;
use App\Models\User;
use App\Services\MercadoLibre\MercadoLibreCatalogSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MercadoLibreRealSyncAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_settings_and_products_expose_mercadolibre_connection_status(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings/Index')
                ->missing('mercadolibreStatus.redirect_uri')
                ->missing('mercadolibreStatus.app_id')
                ->has('mercadolibreStatus.has_credentials')
                ->where('mercadolibreStatus.connected', false)
            );

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Products/Index')
                ->where('mercadolibreConnected', false)
            );

        MarketplaceToken::create([
            'marketplace' => 'mercadolibre',
            'user_id' => '546980216',
            'access_token' => 'APP_USR-test-token',
            'refresh_token' => 'TG-test-refresh',
            'token_type' => 'Bearer',
            'expires_at' => now()->addHours(5),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('mercadolibreStatus.connected', true)
                ->where('mercadolibreStatus.user_id', '546980216')
            );

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('mercadolibreConnected', true)
            );
    }

    public function test_sync_batch_persists_products_with_valid_condition_aligned_categories_and_unique_skus(): void
    {
        $clientMock = $this->createMock(MarketplaceClient::class);
        $clientMock->method('searchListingsWithScroll')
            ->willReturn([
                'results' => ['MLM100001', 'MLM100002'],
                'scroll_id' => null,
                'total' => 2,
            ]);

        // Dos publicaciones reales distintas que comparten el mismo PART_NUMBER
        $clientMock->method('fetchListings')
            ->willReturn([
                [
                    'id' => 'MLM100001',
                    'title' => 'Faro Delantero Derecho Nissan Versa 2018',
                    'price' => 2400,
                    'available_quantity' => 1,
                    'status' => 'active',
                    'category_id' => 'MLM187516',
                    'permalink' => 'https://articulo.mercadolibre.com.mx/MLM-100001',
                    'attributes' => [
                        ['id' => 'BRAND', 'value_name' => 'Nissan'],
                        ['id' => 'MODEL', 'value_name' => 'Versa'],
                        ['id' => 'PART_NUMBER', 'value_name' => 'SAME-PN-01'],
                    ],
                    'pictures' => [
                        ['secure_url' => 'https://http2.mlstatic.com/D_NQ_NP_111.jpg'],
                    ],
                ],
                [
                    'id' => 'MLM100002',
                    'title' => 'Faro Delantero Izquierdo Nissan Versa 2018',
                    'price' => 2400,
                    'available_quantity' => 2,
                    'status' => 'active',
                    'category_id' => 'MLM187516',
                    'permalink' => 'https://articulo.mercadolibre.com.mx/MLM-100002',
                    'attributes' => [
                        ['id' => 'BRAND', 'value_name' => 'Nissan'],
                        ['id' => 'MODEL', 'value_name' => 'Versa'],
                        ['id' => 'PART_NUMBER', 'value_name' => 'SAME-PN-01'],
                    ],
                    'pictures' => [
                        ['secure_url' => 'https://http2.mlstatic.com/D_NQ_NP_222.jpg'],
                    ],
                ],
            ]);

        $this->app->instance(MarketplaceClient::class, $clientMock);

        $syncService = app(MercadoLibreCatalogSyncService::class);
        $batch = $syncService->syncBatch([
            'limit' => 50,
            'user_id' => '546980216',
        ]);

        $this->assertSame(2, $batch['processed_count']);
        $this->assertSame(2, $batch['created_count']);
        $this->assertSame(0, $batch['failed_count']);

        $products = Product::query()->orderBy('id')->get();
        $this->assertCount(2, $products);
        $this->assertSame('A', $products[0]->condition);
        $this->assertSame('Colision', $products[0]->category);
        $this->assertNotSame($products[0]->sku, $products[1]->sku);

        // El filtro de categoría público debe encontrar las piezas importadas
        $this->get(route('catalog.index', ['category' => 'Colision']))
            ->assertStatus(200)
            ->assertSee('Faro Delantero Derecho Nissan Versa 2018')
            ->assertSee('Faro Delantero Izquierdo Nissan Versa 2018');
    }
}
