<?php

namespace Tests\Unit;

use App\Contracts\MarketplaceClient;
use App\Models\MarketplaceRawItem;
use App\Services\MercadoLibre\MercadoLibreInspectorService;
use App\Services\MercadoLibre\MercadoLibreOAuthService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MercadoLibreInspectorTest extends TestCase
{
    private string $tempSamplePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempSamplePath = storage_path('app/temp_test_meli_sample.json');
    }

    protected function tearDown(): void
    {
        if (File::exists($this->tempSamplePath)) {
            File::delete($this->tempSamplePath);
        }
        parent::tearDown();
    }

    public function test_load_and_store_from_fixture_loads_autoparts_structure(): void
    {
        $fixturePath = database_path('fixtures/mercadolibre_autoparts_fixture.json');
        $this->assertFileExists($fixturePath);

        $content = File::get($fixturePath);
        $items = json_decode($content, true);

        $this->assertIsArray($items);
        $this->assertGreaterThanOrEqual(8, count($items));

        $firstItem = $items[0];
        $this->assertEquals('MLM3001001', $firstItem['id']);
        $this->assertEquals('Nissan', $this->extractAttribute($firstItem, 'BRAND'));
        $this->assertEquals('Versa', $this->extractAttribute($firstItem, 'MODEL'));
        $this->assertEquals('HR16DE-7782', $firstItem['attributes'][3]['value_name']);
        $this->assertArrayHasKey('shipping', $firstItem);
        $this->assertArrayHasKey('dimensions', $firstItem['shipping']);
    }

    public function test_export_sample_json_creates_valid_json_file(): void
    {
        $fixturePath = database_path('fixtures/mercadolibre_autoparts_fixture.json');
        $items = json_decode(File::get($fixturePath), true);

        File::put($this->tempSamplePath, json_encode($items, JSON_PRETTY_PRINT));
        $this->assertFileExists($this->tempSamplePath);

        $decoded = json_decode(File::get($this->tempSamplePath), true);
        $this->assertIsArray($decoded);
        $this->assertCount(count($items), $decoded);
    }

    public function test_raw_item_model_attribute_accessor(): void
    {
        $fixturePath = database_path('fixtures/mercadolibre_autoparts_fixture.json');
        $items = json_decode(File::get($fixturePath), true);

        $faroKicks = $items[3]; // Faro LED Kicks
        $model = new MarketplaceRawItem([
            'marketplace' => 'mercadolibre',
            'external_id' => $faroKicks['id'],
            'title' => $faroKicks['title'],
            'status' => $faroKicks['status'],
            'raw_payload' => $faroKicks,
        ]);

        $this->assertEquals('Nissan', $model->getMeliAttribute('BRAND'));
        $this->assertEquals('Kicks', $model->getMeliAttribute('MODEL'));
        $this->assertEquals('26010-5RB0A', $model->getMeliAttribute('PART_NUMBER'));
        $this->assertEquals('Derecho / Pasajero', $model->getMeliAttribute('SIDE'));
        $this->assertEquals('Delantero', $model->getMeliAttribute('POSITION'));
        $this->assertCount(1, $model->getPictureUrls());
    }

    private function extractAttribute(array $item, string $attributeId): ?string
    {
        foreach ($item['attributes'] ?? [] as $attr) {
            if (($attr['id'] ?? null) === $attributeId) {
                return $attr['value_name'] ?? null;
            }
        }

        return null;
    }
}
