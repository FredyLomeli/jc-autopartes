<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MercadoLibre\MercadoLibreOAuthService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MercadoLibreOAuthTest extends TestCase
{
    public function test_guest_cannot_access_mercadolibre_connect(): void
    {
        $response = $this->get('/admin/integraciones/mercadolibre/conectar');

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_access_mercadolibre_disconnect(): void
    {
        $response = $this->delete('/admin/integraciones/mercadolibre/desconectar');

        $response->assertRedirect('/login');
    }

    public function test_connect_redirects_to_settings_with_error_when_credentials_missing(): void
    {
        Config::set('mercadolibre.app_id', null);
        Config::set('mercadolibre.client_secret', null);

        $user = new User([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@jcautopartes.mx',
        ]);

        $response = $this->actingAs($user)->get('/admin/integraciones/mercadolibre/conectar');

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('error');
    }

    public function test_connect_redirects_to_mercadolibre_when_credentials_present(): void
    {
        Config::set('mercadolibre.app_id', '123456789');
        Config::set('mercadolibre.client_secret', 'secret_key');
        Config::set('mercadolibre.redirect_uri', 'http://localhost:8000/admin/integraciones/mercadolibre/callback');
        Config::set('mercadolibre.auth_base_url', 'https://auth.mercadolibre.com.mx');

        $user = new User([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@jcautopartes.mx',
        ]);

        $response = $this->actingAs($user)->get('/admin/integraciones/mercadolibre/conectar');

        $response->assertRedirect();
        $this->assertStringStartsWith('https://auth.mercadolibre.com.mx/authorization?', $response->headers->get('Location'));
        $this->assertNotNull(session('meli_oauth_state'));
    }

    public function test_callback_handles_remote_error(): void
    {
        $user = new User([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@jcautopartes.mx',
        ]);

        $response = $this->actingAs($user)->get('/admin/integraciones/mercadolibre/callback?error=access_denied&error_description=User+denied+access');

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('error');
    }

    public function test_callback_validates_state_parameter(): void
    {
        $user = new User([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@jcautopartes.mx',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['meli_oauth_state' => 'expected_state_value'])
            ->get('/admin/integraciones/mercadolibre/callback?code=TG-12345&state=mismatched_state_value');

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('error');
    }
}
