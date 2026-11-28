<?php

namespace Tests\Feature;

use App\Models\HomeBanner;
use App\Models\Product;
use App\Services\CompanySettingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicUiRefinementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_header_uses_compact_dark_bar_and_preserves_red_navigation_bar(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('h-12 sm:h-16 md:h-20', $content);
        $this->assertStringContainsString('bg-primary text-white shadow-sm', $content);
    }

    public function test_home_banners_render_without_cropping_and_relocate_cta_out_of_mobile_image(): void
    {
        HomeBanner::query()->create([
            'title' => 'Refacciones Garantizadas',
            'subtitle' => 'Envíos a todo México',
            'image_path' => 'banners/demo.jpg',
            'button_text' => 'Explorar catálogo',
            'button_url' => '/refacciones',
            'order' => 1,
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('w-full h-auto block', $content);
        $this->assertStringContainsString('hidden md:block absolute inset-0 bg-gradient-to-l', $content);
        $this->assertStringContainsString('top-[65%] -translate-y-1/2', $content);
        $this->assertStringContainsString('md:hidden bg-dark text-white px-4 py-3.5', $content);
    }

    public function test_catalog_cards_and_product_detail_render_full_product_images_without_cropping(): void
    {
        $product = Product::query()->where('sku', 'JC-NIS-001')->firstOrFail();

        $catalogResponse = $this->get('/refacciones');
        $catalogResponse->assertOk();
        $catalogResponse->assertSee('object-contain w-full h-48', false);

        $detailResponse = $this->get(route('catalog.show', $product->slug));
        $detailResponse->assertOk();
        $detailResponse->assertSee('w-full h-full object-contain bg-white p-2', false);
    }

    public function test_product_detail_uses_editable_whatsapp_setting_and_provides_share_controls(): void
    {
        $product = Product::query()->where('sku', 'JC-NIS-001')->firstOrFail();

        app(CompanySettingService::class)->set('contact_whatsapp_1', '378 999 8877', [
            'group' => 'contact',
            'type' => 'string',
            'label' => 'WhatsApp Ventas Línea 1',
        ]);

        $response = $this->get(route('catalog.show', $product->slug));

        $response->assertOk();
        $response->assertSee('https://wa.me/523789998877?text=', false);
        $response->assertSee('Consultar por WhatsApp · 378 999 8877', false);
        $response->assertSee('Copiar / Compartir enlace', false);
        $response->assertSee('Compartir por WhatsApp', false);

        $expectedProductUrl = route('catalog.show', $product->slug);
        $response->assertSee(urlencode($expectedProductUrl), false);
    }
}
