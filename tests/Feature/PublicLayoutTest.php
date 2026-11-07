<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLayoutTest extends TestCase
{
    use RefreshDatabase;
    public function test_home_page_loads_with_blade_layout_and_official_business_data(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('JC Autopartes', false);
        $response->assertSee('378 145 5737', false);
        $response->assertSee('jcautopartes.mx@gmail.com', false);
        $response->assertSee('¿Qué nos hace diferentes?', false);
        $response->assertSee('¡Estas son las marcas con las que trabajamos!', false);
        $response->assertSee('Nuestra Filosofía Empresarial', false);
    }

    public function test_home_page_includes_brand_logos_banners_and_tepatitlan_facilities_gallery(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('imagenes/brand/logo.jpg', false);
        $response->assertSee('¿Qué nos hace diferentes?', false);
        $response->assertSee('Garantía Escrita', false);
    }

    public function test_home_page_contains_exactly_one_h1_tag_and_canonical_link(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertSame(1, substr_count($content, '<h1'));
        $this->assertStringContainsString('<link rel="canonical"', $content);
        $this->assertStringNotContainsString('data-page=', $content);
    }

    public function test_design_tokens_are_centralized_in_css_and_tailwind_config(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $tailwind = file_get_contents(base_path('tailwind.config.js'));

        $this->assertStringContainsString('--color-primary: #E31221;', $css);
        $this->assertStringContainsString('--color-dark: #111111;', $css);
        $this->assertStringContainsString('--color-surface: #F8F9FA;', $css);
        $this->assertStringContainsString('var(--color-primary)', $tailwind);
        $this->assertStringContainsString('var(--color-dark)', $tailwind);
        $this->assertStringContainsString('var(--color-surface)', $tailwind);
    }
}
