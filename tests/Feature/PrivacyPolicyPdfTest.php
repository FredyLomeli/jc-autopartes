<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SiteSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivacyPolicyPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_page_works_without_pdf_and_pdf_endpoint_returns_404(): void
    {
        $this->seed(SiteSettingsSeeder::class);

        $this->get(route('pages.privacy'))
            ->assertStatus(200)
            ->assertSee('Aviso de Privacidad Integral')
            ->assertDontSee('Documento Oficial de Políticas y Aviso de Privacidad (PDF)');

        $this->get(route('pages.privacy.pdf'))
            ->assertStatus(404);
    }

    public function test_admin_can_upload_view_and_delete_privacy_policy_pdf(): void
    {
        Storage::fake('public');
        $this->seed(SiteSettingsSeeder::class);
        $admin = User::factory()->create();

        // Rechaza archivo que no sea PDF
        $invalidFile = UploadedFile::fake()->create('politica.txt', 20, 'text/plain');
        $this->actingAs($admin)
            ->post(route('admin.settings.privacy-pdf.upload'), [
                'privacy_pdf' => $invalidFile,
            ])
            ->assertSessionHasErrors('privacy_pdf');

        // Sube PDF válido
        $pdfFile = UploadedFile::fake()->create('aviso-privacidad.pdf', 120, 'application/pdf');
        $this->actingAs($admin)
            ->post(route('admin.settings.privacy-pdf.upload'), [
                'privacy_pdf' => $pdfFile,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        // La página pública de privacidad muestra el visor PDF y el endpoint sirve el PDF inline
        $this->get(route('pages.privacy'))
            ->assertStatus(200)
            ->assertSee('Documento Oficial de Políticas y Aviso de Privacidad (PDF)')
            ->assertSee(route('pages.privacy.pdf'), false);

        $pdfResponse = $this->get(route('pages.privacy.pdf'));
        $pdfResponse->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $pdfResponse->headers->get('content-type'));

        // Eliminar PDF revierte al estado textual y devuelve 404 en el endpoint
        $this->actingAs($admin)
            ->delete(route('admin.settings.privacy-pdf.destroy'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->get(route('pages.privacy.pdf'))->assertStatus(404);
        $this->get(route('pages.privacy'))
            ->assertStatus(200)
            ->assertDontSee('Documento Oficial de Políticas y Aviso de Privacidad (PDF)');
    }

    public function test_warranty_page_shows_only_pdf_as_general_metric_and_supports_upload_and_delete(): void
    {
        Storage::fake('public');
        $this->seed(SiteSettingsSeeder::class);
        $admin = User::factory()->create();

        // Sin PDF cargado: no muestra los bloques antiguos de plazos y devuelve 404 en el PDF
        $this->get(route('pages.warranty'))
            ->assertStatus(200)
            ->assertSee('Política de Garantía Oficial por Escrito')
            ->assertSee('Documento de Política de Garantía en Actualización')
            ->assertDontSee('Plazos de Cobertura por Categoría de Autoparte');

        $this->get(route('pages.warranty.pdf'))->assertStatus(404);

        // Sube PDF válido de garantía
        $pdfFile = UploadedFile::fake()->create('garantias-jc.pdf', 150, 'application/pdf');
        $this->actingAs($admin)
            ->post(route('admin.settings.warranty-pdf.upload'), [
                'warranty_pdf' => $pdfFile,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->get(route('pages.warranty'))
            ->assertStatus(200)
            ->assertSee('Documento Oficial de Garantías (PDF)')
            ->assertSee(route('pages.warranty.pdf'), false)
            ->assertDontSee('Plazos de Cobertura por Categoría de Autoparte');

        $pdfResponse = $this->get(route('pages.warranty.pdf'));
        $pdfResponse->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $pdfResponse->headers->get('content-type'));

        // Elimina el PDF de garantía
        $this->actingAs($admin)
            ->delete(route('admin.settings.warranty-pdf.destroy'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->get(route('pages.warranty.pdf'))->assertStatus(404);
    }
}
