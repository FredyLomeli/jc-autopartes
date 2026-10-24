<?php

namespace Tests\Unit;

use App\Contracts\MarketplaceClient;
use App\Services\MercadoLibre\MercadoLibreCatalogSyncService;
use App\Services\MercadoLibre\MercadoLibreInspectorService;
use App\Services\MercadoLibre\MercadoLibreOAuthService;
use App\Services\MercadoLibre\MercadoLibreProductMapper;
use App\Services\PriceService;
use App\Services\StockService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MercadoLibreCatalogSyncServiceTest extends TestCase
{
    public function test_sync_processes_fixture_payloads_cleanly(): void
    {
        $mapper = new MercadoLibreProductMapper();
        $stockService = $this->createMock(StockService::class);
        $priceService = $this->createMock(PriceService::class);
        $inspector = $this->createMock(MercadoLibreInspectorService::class);

        $syncService = new MercadoLibreCatalogSyncService(
            $mapper,
            $stockService,
            $priceService,
            $inspector
        );

        $result = $syncService->sync([
            'fixture' => true,
            'limit' => 2,
        ]);

        $this->assertSame(2, $result['total']);
        $this->assertCount(2, $result['items']);
        $this->assertArrayHasKey('mercadolibre_id', $result['items'][0]);
        $this->assertArrayHasKey('title', $result['items'][0]);
        $this->assertArrayHasKey('sku', $result['items'][0]);
        $this->assertArrayHasKey('price', $result['items'][0]);
        $this->assertArrayHasKey('stock', $result['items'][0]);
    }

    public function test_obtain_payloads_returns_array(): void
    {
        $mapper = new MercadoLibreProductMapper();
        $stockService = $this->createMock(StockService::class);
        $priceService = $this->createMock(PriceService::class);
        $inspector = $this->createMock(MercadoLibreInspectorService::class);

        $syncService = new MercadoLibreCatalogSyncService(
            $mapper,
            $stockService,
            $priceService,
            $inspector
        );

        $payloads = $syncService->obtainPayloads(fromFixture: true, limit: 3);
        $this->assertCount(3, $payloads);
        $this->assertIsArray($payloads[0]);
        $this->assertArrayHasKey('id', $payloads[0]);
    }

    public function test_sync_batch_processes_slice_and_generates_report(): void
    {
        $mapper = new MercadoLibreProductMapper();
        $stockService = $this->createMock(StockService::class);
        $priceService = $this->createMock(PriceService::class);
        $inspector = $this->createMock(MercadoLibreInspectorService::class);

        $syncService = new MercadoLibreCatalogSyncService(
            $mapper,
            $stockService,
            $priceService,
            $inspector
        );

        $batch = $syncService->syncBatch([
            'fixture' => true,
            'limit' => 2,
            'offset' => 0,
        ]);

        $this->assertSame(2, $batch['processed_count']);
        $this->assertSame(2, $batch['next_offset']);
        $this->assertTrue($batch['has_more']);
        $this->assertCount(2, $batch['items']);
        $this->assertFileExists($batch['report_path']);

        $report = $syncService->getDiagnosticReport();
        $this->assertArrayHasKey('estado_general', $report);
        $this->assertArrayHasKey('resumen', $report);
        $this->assertSame(2, $report['resumen']['total_procesados_acumulados']);
    }

    public function test_sync_batch_with_client_handles_remote_batches(): void
    {
        $mapper = new MercadoLibreProductMapper();
        $stockService = $this->createMock(StockService::class);
        $priceService = $this->createMock(PriceService::class);
        $inspector = $this->createMock(MercadoLibreInspectorService::class);

        $clientMock = $this->createMock(MarketplaceClient::class);
        $clientMock->method('searchListingsWithScroll')
            ->willReturn([
                'results' => ['MLM3001001', 'MLM3001002'],
                'scroll_id' => 'next_scroll_token_xyz',
                'total' => 12000,
            ]);

        $clientMock->method('fetchListings')
            ->willReturn([
                [
                    'id' => 'MLM3001001',
                    'title' => 'Motor 3/4 Versa',
                    'price' => 18000,
                    'available_quantity' => 1,
                    'status' => 'active',
                    'category_id' => 'MLM187514',
                    'attributes' => [
                        ['id' => 'BRAND', 'value_name' => 'Nissan'],
                        ['id' => 'PART_NUMBER', 'value_name' => 'HR16'],
                    ],
                ],
                [
                    'id' => 'MLM3001002',
                    'title' => 'Transmisión Estándar Versa',
                    'price' => 9500,
                    'available_quantity' => 1,
                    'status' => 'active',
                    'category_id' => 'MLM187514',
                    'attributes' => [
                        ['id' => 'BRAND', 'value_name' => 'Nissan'],
                        ['id' => 'PART_NUMBER', 'value_name' => 'TR-VER'],
                    ],
                ],
            ]);

        $syncService = new MercadoLibreCatalogSyncService(
            $mapper,
            $stockService,
            $priceService,
            $inspector,
            $clientMock
        );

        $batch = $syncService->syncBatch([
            'limit' => 2,
            'scroll_id' => null,
            'user_id' => '123456',
        ]);

        $this->assertSame(2, $batch['processed_count']);
        $this->assertSame('next_scroll_token_xyz', $batch['next_scroll_id']);
        $this->assertTrue($batch['has_more']);
        $this->assertSame(12000, $batch['total_remote']);
    }
}
