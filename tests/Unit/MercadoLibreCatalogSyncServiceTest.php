<?php

namespace Tests\Unit;

use App\Services\MercadoLibre\MercadoLibreCatalogSyncService;
use App\Services\MercadoLibre\MercadoLibreInspectorService;
use App\Services\MercadoLibre\MercadoLibreProductMapper;
use App\Services\PriceService;
use App\Services\StockService;
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
}
