<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogAndSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_home_page_displays_featured_products_and_dynamic_vehicle_options(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Piezas Destacadas en Inventario', false);
        $response->assertSee('Motor 3/4 1.6L HR16DE Nissan Versa Original', false);
        $response->assertSee('NP300 Frontier', false);
        $response->assertSee('Silverado', false);
    }

    public function test_catalog_filters_results_and_excludes_draft_products(): void
    {
        // El seeder incluye 35 publicados y 1 en borrador (JC-VW-006)
        $responseAll = $this->get('/refacciones');
        $responseAll->assertStatus(200);
        $responseAll->assertDontSee('JC-VW-006', false);
        $this->assertSame(1, substr_count($responseAll->getContent(), '<h1'));

        // Filtro por marca + categoría
        $responseFiltered = $this->get('/refacciones?brand=Nissan&category=Motores');
        $responseFiltered->assertStatus(200);
        $responseFiltered->assertSee('JC-NIS-001', false);
        $responseFiltered->assertDontSee('JC-CHE-001', false);

        // Filtro por año compatible (2017 para Nissan Kicks 2017-2020)
        $responseYear = $this->get('/refacciones?brand=Nissan&model=Kicks&year=2018');
        $responseYear->assertStatus(200);
        $responseYear->assertSee('JC-NIS-004', false);

        // Filtro por condición B
        $responseCondition = $this->get('/refacciones?condition=B');
        $responseCondition->assertStatus(200);
        $responseCondition->assertSee('JC-NIS-005', false);
        $responseCondition->assertDontSee('JC-NIS-001', false);

        // Filtro por rango de precio
        $responsePrice = $this->get('/refacciones?min_price=40000&max_price=50000');
        $responsePrice->assertStatus(200);
        $responsePrice->assertSee('JC-CHE-001', false);
        $responsePrice->assertDontSee('JC-NIS-001', false);
    }

    public function test_filtered_catalog_page_has_clean_canonical_pointing_to_unfiltered_catalog(): void
    {
        $response = $this->get('/refacciones?brand=Toyota&category=Motores&condition=A&sort=price_asc');

        $response->assertStatus(200);
        $expectedCanonical = route('catalog.index');
        $response->assertSee('<link rel="canonical" href="' . $expectedCanonical . '">', false);
    }

    public function test_product_detail_page_renders_specs_whatsapp_link_and_valid_json_ld(): void
    {
        $product = Product::query()->where('sku', 'JC-NIS-001')->firstOrFail();

        $response = $this->get(route('catalog.show', $product->slug));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertSame(1, substr_count($content, '<h1'));
        $response->assertSee($product->title, false);
        $response->assertSee($product->sku, false);
        $response->assertSee('$18,500.00 MXN', false);
        $response->assertSee('Garantía 30 Días', false);
        $response->assertSee('https://wa.me/523331665290?text=', false);
        $response->assertSee(urlencode($product->title), false);

        // Extraer y validar JSON-LD
        preg_match('/<script type="application\/ld\+json">\s*(\{.*?\})\s*<\/script>/s', $content, $matches);
        $this->assertNotEmpty($matches[1] ?? null, 'No se encontró el bloque JSON-LD en la ficha.');

        $decoded = json_decode($matches[1], true);
        $this->assertIsArray($decoded);
        $this->assertSame('https://schema.org', $decoded['@context']);
        $this->assertSame('Product', $decoded['@type']);
        $this->assertSame($product->title, $decoded['name']);
        $this->assertSame($product->sku, $decoded['sku']);
        $this->assertSame($product->part_number ?? $product->sku, $decoded['mpn']);
        $this->assertSame('MXN', $decoded['offers']['priceCurrency']);
        $this->assertSame('18500.00', $decoded['offers']['price']);
        $this->assertSame('https://schema.org/InStock', $decoded['offers']['availability']);
    }

    public function test_draft_product_returns_404_on_public_detail_page(): void
    {
        $draft = Product::query()->where('sku', 'JC-VW-006')->firstOrFail();

        $response = $this->get(route('catalog.show', $draft->slug));
        $response->assertNotFound();
    }

    public function test_static_pages_and_sitemap_load_correctly(): void
    {
        foreach (['/contacto', '/garantia', '/aviso-de-privacidad', '/terminos-y-condiciones'] as $uri) {
            $res = $this->get($uri);
            $res->assertStatus(200);
            $this->assertSame(1, substr_count($res->getContent(), '<h1'), "La ruta {$uri} debe tener exactamente un H1.");
        }

        $sitemap = $this->get('/sitemap.xml');
        $sitemap->assertStatus(200);
        $sitemap->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $published = Product::query()->where('sku', 'JC-NIS-001')->firstOrFail();
        $draft = Product::query()->where('sku', 'JC-VW-006')->firstOrFail();

        $sitemap->assertSee(route('catalog.show', $published->slug), false);
        $sitemap->assertDontSee(route('catalog.show', $draft->slug), false);
    }
}
