<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CompanySettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanySettingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_returns_cached_settings(): void
    {
        SiteSetting::create([
            'key' => 'contact_phone_office',
            'value' => '378 688 2205',
            'group' => 'contact',
            'type' => 'string',
        ]);

        $service = app(CompanySettingService::class);

        $this->assertEquals('378 688 2205', $service->get('contact_phone_office'));
        $this->assertEquals('default', $service->get('non_existent', 'default'));
    }

    public function test_authenticated_user_can_view_and_update_settings(): void
    {
        $user = User::factory()->create();

        SiteSetting::create([
            'key' => 'company_name',
            'value' => 'JC Autopartes',
            'group' => 'general',
            'type' => 'string',
        ]);

        $response = $this->actingAs($user)->get(route('admin.settings.index'));
        $response->assertStatus(200);

        $updateResponse = $this->actingAs($user)->put(route('admin.settings.update'), [
            'settings' => [
                'company_name' => 'JC Autopartes Tepatitlán Oficial',
            ],
        ]);

        $updateResponse->assertSessionHas('success');
        $this->assertDatabaseHas('site_settings', [
            'key' => 'company_name',
            'value' => 'JC Autopartes Tepatitlán Oficial',
        ]);
    }

    public function test_authenticated_user_can_upload_and_reset_brand_logo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $upload = $this->actingAs($user)->post(route('admin.settings.logo.upload'), [
            'logo' => UploadedFile::fake()->image('jc-logo-oscuro.png', 400, 200),
        ]);

        $upload->assertSessionHas('success');
        $storedPath = app(CompanySettingService::class)->get('company_logo_path');
        $this->assertNotEmpty($storedPath);
        Storage::disk('public')->assertExists($storedPath);

        // El sitio público debe mostrar el logotipo cargado
        $this->get('/')->assertStatus(200)->assertSee('storage/' . $storedPath, false);

        // Restablecer al predeterminado
        $destroy = $this->actingAs($user)->delete(route('admin.settings.logo.destroy'));
        $destroy->assertSessionHas('success');
        Storage::disk('public')->assertMissing($storedPath);
        $this->assertSame('', (string) app(CompanySettingService::class)->get('company_logo_path'));
    }
}
