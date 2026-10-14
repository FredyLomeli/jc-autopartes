<?php

namespace Tests\Unit;

use App\Services\MercadoLibre\MercadoLibreProductMapper;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MercadoLibreProductMapperTest extends TestCase
{
    private array $fixtureItems;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('mercadolibre.price_factor', 1.0);

        $path = database_path('fixtures/mercadolibre_autoparts_fixture.json');
        $this->fixtureItems = json_decode(File::get($path), true);
    }

    public function test_mapper_transforms_motor_payload_correctly(): void
    {
        $motorPayload = $this->fixtureItems[0]; // Motor 3/4 Versa
        $mapper = new MercadoLibreProductMapper();

        $result = $mapper->map($motorPayload);

        $attrs = $result['attributes'];
        $this->assertEquals('MLM3001001', $attrs['mercadolibre_id']);
        $this->assertEquals('Motor 3/4 1.6L HR16DE Nissan Versa 2015-2019 Original Garantizado', $attrs['title']);
        $this->assertEquals('HR16DE-7782', $attrs['part_number']);
        $this->assertEquals('10102-1HA0A', $attrs['oem_number']);
        $this->assertEquals('HR16DE-7782', $attrs['sku']);
        $this->assertEquals('Motores', $attrs['category']);
        $this->assertEquals('Nissan', $attrs['vehicle_brand']);
        $this->assertEquals('Versa', $attrs['vehicle_model']);
        $this->assertEquals(2015, $attrs['year_start']);
        $this->assertEquals(2019, $attrs['year_end']);
        $this->assertEquals('used', $attrs['condition']);
        $this->assertEquals('published', $attrs['status']);
        $this->assertEquals(85.0, $attrs['height_cm']);
        $this->assertEquals(65.0, $attrs['width_cm']);
        $this->assertEquals(70.0, $attrs['length_cm']);
        $this->assertEquals(85.0, $attrs['weight_kg']);

        $this->assertEquals(18500.0, $result['target_price']);
        $this->assertEquals(2, $result['target_stock']);
        $this->assertCount(2, $result['photo_urls']);
    }

    public function test_mapper_transforms_collision_part_with_position_and_side(): void
    {
        $faroPayload = $this->fixtureItems[3]; // Faro Kicks
        $mapper = new MercadoLibreProductMapper();

        $result = $mapper->map($faroPayload);
        $attrs = $result['attributes'];

        $this->assertEquals('MLM3001004', $attrs['mercadolibre_id']);
        $this->assertEquals('Colisión y Carrocería', $attrs['category']);
        $this->assertEquals('Derecho / Pasajero', $attrs['side']);
        $this->assertEquals('Delantero', $attrs['position']);
        $this->assertEquals('26010-5RB0A', $attrs['part_number']);
        $this->assertEquals(3.8, $attrs['weight_kg']);
    }

    public function test_mapper_applies_web_price_factor_discount(): void
    {
        Config::set('mercadolibre.price_factor', 0.90); // 10% de descuento web
        $motorPayload = $this->fixtureItems[0]; // $18,500 base

        $mapper = new MercadoLibreProductMapper();
        $result = $mapper->map($motorPayload);

        // 18,500 * 0.90 = 16,650.00
        $this->assertEquals(16650.0, $result['target_price']);
    }

    public function test_mapper_sets_draft_status_when_stock_is_zero(): void
    {
        $payload = $this->fixtureItems[0];
        $payload['available_quantity'] = 0;

        $mapper = new MercadoLibreProductMapper();
        $result = $mapper->map($payload);

        $this->assertEquals('draft', $result['attributes']['status']);
        $this->assertEquals(0, $result['target_stock']);
    }

    public function test_extract_years_supports_multiple_title_formats(): void
    {
        $mapper = new MercadoLibreProductMapper();

        $this->assertEquals([2016, 2021], $mapper->extractYears('Faro Nissan March 2016-2021 Original', null));
        $this->assertEquals([2018, 2022], $mapper->extractYears('Transmisión Aveo 2018 a 2022 Estándar', null));
        $this->assertEquals([2017, 2017], $mapper->extractYears('Alternador Versa 2017 Original', null));
        $this->assertEquals([2019, 2019], $mapper->extractYears('Cofre Versa Original', '2019'));
    }

    public function test_parse_dimensions_converts_grams_to_kg_accurately(): void
    {
        $mapper = new MercadoLibreProductMapper();

        $dims = $mapper->parseDimensions('64x32x28,3800', 'Colisión y Carrocería');
        $this->assertEquals(64.0, $dims['height_cm']);
        $this->assertEquals(32.0, $dims['width_cm']);
        $this->assertEquals(28.0, $dims['length_cm']);
        $this->assertEquals(3.8, $dims['weight_kg']);
    }
}
