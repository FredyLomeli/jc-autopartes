<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\MercadoLibreSyncController;
use App\Services\MercadoLibre\MercadoLibreCatalogSyncService;
use App\Services\MercadoLibre\MercadoLibreOAuthService;
use Illuminate\Http\Request;
use Tests\TestCase;

class MercadoLibreSyncControllerTest extends TestCase
{
    public function test_process_chunk_with_fixture_returns_json_batch(): void
    {
        $syncServiceMock = $this->createMock(MercadoLibreCatalogSyncService::class);
        $syncServiceMock->method('syncBatch')
            ->willReturn([
                'processed_count' => 2,
                'created_count' => 2,
                'updated_count' => 0,
                'failed_count' => 0,
                'total_remote' => 12000,
                'next_scroll_id' => 'scroll_token_123',
                'next_offset' => 2,
                'has_more' => true,
                'items' => [
                    ['mercadolibre_id' => 'MLM101', 'title' => 'Faro Versa'],
                ],
                'errors' => [],
                'report_path' => storage_path('app/mercadolibre_sync_report.json'),
            ]);

        $oauthMock = $this->createMock(MercadoLibreOAuthService::class);

        $controller = new MercadoLibreSyncController($syncServiceMock, $oauthMock);

        $request = Request::create('/admin/integraciones/mercadolibre/sincronizar-lote', 'POST', [
            'fixture' => true,
            'limit' => 2,
            'offset' => 0,
        ]);

        $response = $controller->processChunk($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertSame(2, $data['processed_count']);
        $this->assertSame('scroll_token_123', $data['next_scroll_id']);
        $this->assertSame(12000, $data['total_remote']);
    }

    public function test_process_chunk_rejects_unauthenticated_non_fixture_call(): void
    {
        $syncServiceMock = $this->createMock(MercadoLibreCatalogSyncService::class);
        $oauthMock = $this->createMock(MercadoLibreOAuthService::class);
        $oauthMock->method('getActiveToken')->willReturn(null);

        $controller = new MercadoLibreSyncController($syncServiceMock, $oauthMock);

        $request = Request::create('/admin/integraciones/mercadolibre/sincronizar-lote', 'POST', [
            'fixture' => false,
            'limit' => 50,
        ]);

        $response = $controller->processChunk($request);

        $this->assertSame(401, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertSame('UNAUTHENTICATED_MELI', $data['error_type']);
    }

    public function test_report_endpoint_returns_json_report(): void
    {
        $syncServiceMock = $this->createMock(MercadoLibreCatalogSyncService::class);
        $syncServiceMock->method('getDiagnosticReport')
            ->willReturn([
                'estado_general' => 'EXITOSO',
                'resumen' => [
                    'total_procesados_acumulados' => 12000,
                ],
            ]);

        $oauthMock = $this->createMock(MercadoLibreOAuthService::class);

        $controller = new MercadoLibreSyncController($syncServiceMock, $oauthMock);

        $request = Request::create('/admin/integraciones/mercadolibre/reporte', 'GET');
        $response = $controller->report($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame('EXITOSO', $data['estado_general']);
        $this->assertSame(12000, $data['resumen']['total_procesados_acumulados']);
    }

    public function test_reset_endpoint_calls_report_reset(): void
    {
        $syncServiceMock = $this->createMock(MercadoLibreCatalogSyncService::class);
        $syncServiceMock->expects($this->once())
            ->method('generateDiagnosticReport')
            ->willReturn(storage_path('app/mercadolibre_sync_report.json'));

        $oauthMock = $this->createMock(MercadoLibreOAuthService::class);

        $controller = new MercadoLibreSyncController($syncServiceMock, $oauthMock);

        $response = $controller->reset();

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
    }
}
