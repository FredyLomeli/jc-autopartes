<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MercadoLibre\MercadoLibreCatalogSyncService;
use Tests\TestCase;

class MercadoLibreCatalogSyncRouteTest extends TestCase
{
    public function test_guest_is_redirected_to_login_when_accessing_sync_route(): void
    {
        $response = $this->post(route('admin.products.sync-meli'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_trigger_catalog_sync(): void
    {
        $user = new User();
        $user->id = 1;
        $user->name = 'Admin Test';
        $user->email = 'admin@jcautopartes.mx';

        $mockSyncService = $this->createMock(MercadoLibreCatalogSyncService::class);
        $mockSyncService->expects($this->once())
            ->method('sync')
            ->willReturn([
                'total' => 8,
                'created' => 8,
                'updated' => 0,
                'items' => [],
            ]);

        $this->app->instance(MercadoLibreCatalogSyncService::class, $mockSyncService);

        $response = $this->actingAs($user)->post(route('admin.products.sync-meli'));

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success');
    }
}
