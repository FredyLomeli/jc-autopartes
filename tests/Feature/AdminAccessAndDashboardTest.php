<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminAccessAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_unauthenticated_guest_cannot_access_any_admin_route(): void
    {
        foreach (['/admin', '/admin/articulos', '/admin/pedidos', '/admin/clientes', '/admin/configuracion'] as $uri) {
            $response = $this->get($uri);
            $response->assertRedirect(route('login'));
        }
    }

    public function test_authenticated_admin_can_access_dashboard_with_demo_and_catalog_metrics(): void
    {
        $admin = User::query()->where('email', 'admin@jcautopartes.mx')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->has('catalogStats')
            ->where('catalogStats.total_products', 36)
            ->where('catalogStats.published_products', 35)
            ->where('catalogStats.draft_products', 1)
            ->has('demoCards', 4)
            ->has('recentProducts', 5)
        );
    }

    public function test_authenticated_admin_can_access_placeholder_and_settings_modules(): void
    {
        $admin = User::query()->where('email', 'admin@jcautopartes.mx')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Orders/Index'));

        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Customers/Index'));

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings/Index')
                ->where('business.name', 'JC Autopartes')
            );
    }
}
