<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\CompanySettingService;
use App\Services\MercadoLibre\MercadoLibreProductMapper;
use App\Services\PriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class WebPricingWithoutIvaTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_service_calculates_web_price_without_iva_and_with_ml_discount(): void
    {
        $priceService = app(PriceService::class);

        // (1000 / 1.16) * 0.90 = 775.86
        $this->assertSame(775.86, $priceService->calculateWebPriceFromMercadoLibre(1000.0, 10.0));
        // (2400 / 1.16) * 0.90 = 1862.07
        $this->assertSame(1862.07, $priceService->calculateWebPriceFromMercadoLibre(2400.0, 10.0));
        // (1000 / 1.16) * 0.85 = 732.76
        $this->assertSame(732.76, $priceService->calculateWebPriceFromMercadoLibre(1000.0, 15.0));
        $this->assertSame(0.0, $priceService->calculateWebPriceFromMercadoLibre(0.0, 10.0));
    }

    public function test_recalculate_updates_ml_products_only_and_preserves_manual_products(): void
    {
        $mlProduct = Product::query()->create([
            'mercadolibre_id' => 'MLM111222333',
            'sku' => 'ML-PRICE-1',
            'title' => 'Faro Completo Versa',
            'slug' => 'faro-completo-versa',
            'category' => 'Iluminación',
            'vehicle_brand' => 'Nissan',
            'vehicle_model' => 'Versa',
            'year_start' => 2016,
            'year_end' => 2019,
            'condition' => 'A',
            'price' => 1000.00,
            'mercadolibre_price' => 1000.00,
            'stock' => 1,
            'status' => 'published',
        ]);

        $manualProduct = Product::query()->create([
            'sku' => 'MANUAL-PRICE-1',
            'title' => 'Pieza Manual Sin ML',
            'slug' => 'pieza-manual-sin-ml',
            'category' => 'Motores',
            'vehicle_brand' => 'Ford',
            'vehicle_model' => 'Lobo',
            'year_start' => 2010,
            'year_end' => 2014,
            'condition' => 'A',
            'price' => 5000.00,
            'mercadolibre_price' => null,
            'stock' => 1,
            'status' => 'published',
        ]);

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.settings.recalculate-web-prices'), [
                'discount_percentage' => 10,
            ])
            ->assertRedirect();

        $mlProduct->refresh();
        $manualProduct->refresh();

        $this->assertSame('1000.00', (string) $mlProduct->mercadolibre_price);
        $this->assertSame('775.86', (string) $mlProduct->price);
        $this->assertSame('5000.00', (string) $manualProduct->price);
        $this->assertSame('10', (string) app(CompanySettingService::class)->get('ml_web_discount_percentage'));
    }

    public function test_mapper_stores_raw_ml_price_and_computes_net_web_price(): void
    {
        Config::set('mercadolibre.price_factor', 1.0);
        app(CompanySettingService::class)->set('ml_web_discount_percentage', '10', [
            'group' => 'social',
            'type' => 'number',
            'label' => 'Descuento ML',
        ]);

        $mapper = app(MercadoLibreProductMapper::class);
        $mapped = $mapper->map([
            'id' => 'MLM777888999',
            'title' => 'Calavera Derecha Nissan March 2018',
            'price' => 1160.00,
            'available_quantity' => 1,
            'status' => 'active',
            'attributes' => [
                ['id' => 'BRAND', 'value_name' => 'Nissan'],
                ['id' => 'MODEL', 'value_name' => 'March'],
            ],
            'pictures' => [],
        ]);

        // (1160 / 1.16) * 0.90 = 900.00
        $this->assertSame(1160.0, $mapped['attributes']['mercadolibre_price']);
        $this->assertSame(900.0, $mapped['target_price']);
    }

    public function test_public_views_label_prices_without_iva(): void
    {
        $product = Product::query()->create([
            'sku' => 'PUB-IVA-1',
            'title' => 'Alternador Nissan Sentra 2017',
            'slug' => 'alternador-nissan-sentra-2017',
            'category' => 'Eléctrico y Sensores',
            'vehicle_brand' => 'Nissan',
            'vehicle_model' => 'Sentra',
            'year_start' => 2015,
            'year_end' => 2019,
            'condition' => 'A',
            'price' => 775.86,
            'mercadolibre_price' => 1000.00,
            'stock' => 2,
            'status' => 'published',
        ]);

        $this->get('/refacciones')
            ->assertOk()
            ->assertSee('Precio sin IVA', false)
            ->assertSee('$775.86 MXN', false);

        $this->get(route('catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Precio sin IVA', false)
            ->assertSee('+ IVA', false);
    }
}
