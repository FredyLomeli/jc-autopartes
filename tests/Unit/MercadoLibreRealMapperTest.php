<?php

namespace Tests\Unit;

use App\Services\MercadoLibre\MercadoLibreProductMapper;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MercadoLibreRealMapperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('mercadolibre.price_factor', 1.0);
    }

    public function test_mapper_parses_two_digit_years_seller_package_dimensions_and_seller_sku(): void
    {
        $mapper = new MercadoLibreProductMapper();

        $payload = [
            'id' => 'MLM846258536',
            'title' => 'Palanca De Velocidades Hyundai Sonata 15-17 Automático',
            'price' => 1450.0,
            'available_quantity' => 1,
            'status' => 'active',
            'category_id' => 'MLM5555',
            'shipping' => ['dimensions' => null],
            'pictures' => [
                ['secure_url' => 'https://http2.mlstatic.com/D_NQ_NP_123456-MLM.jpg'],
            ],
            'attributes' => [
                ['id' => 'BRAND', 'value_name' => 'Hyundai'],
                ['id' => 'PART_NUMBER', 'value_name' => 'Sonata'],
                ['id' => 'SELLER_SKU', 'value_name' => 'HySON15-260'],
                ['id' => 'SELLER_PACKAGE_HEIGHT', 'value_name' => '40 cm'],
                ['id' => 'SELLER_PACKAGE_WIDTH', 'value_name' => '30 cm'],
                ['id' => 'SELLER_PACKAGE_LENGTH', 'value_name' => '7 cm'],
                ['id' => 'SELLER_PACKAGE_WEIGHT', 'value_name' => '2025 g'],
            ],
        ];

        $result = $mapper->map($payload);
        $attrs = $result['attributes'];

        $this->assertSame('Hyundai', $attrs['vehicle_brand']);
        $this->assertSame('Sonata', $attrs['vehicle_model']);
        $this->assertSame(2015, $attrs['year_start']);
        $this->assertSame(2017, $attrs['year_end']);
        $this->assertSame('HYSON15-260', $attrs['sku']);
        $this->assertSame(40.0, $attrs['height_cm']);
        $this->assertSame(30.0, $attrs['width_cm']);
        $this->assertSame(7.0, $attrs['length_cm']);
        $this->assertSame(2.03, $attrs['weight_kg']);
    }

    public function test_mapper_cleans_contaminated_brand_model_and_extracts_side_position(): void
    {
        $mapper = new MercadoLibreProductMapper();

        // Caso 1: BRAND dice Ford y SIDE_POSITION dice Izquierdo/Derecho, pero el título es Visera Chevrolet Cavalier
        $payload1 = [
            'id' => 'MLM849115346',
            'title' => 'Visera Chevrolet Cavalier 2018-2020 (izquierda)',
            'price' => 590.0,
            'available_quantity' => 1,
            'status' => 'active',
            'attributes' => [
                ['id' => 'BRAND', 'value_name' => 'Ford'],
                ['id' => 'SELLER_SKU', 'value_name' => 'ChCAV18-1101'],
                ['id' => 'SIDE_POSITION', 'value_name' => 'Izquierdo/Derecho'],
            ],
        ];

        $res1 = $mapper->map($payload1)['attributes'];
        $this->assertSame('Chevrolet', $res1['vehicle_brand']);
        $this->assertSame('Cavalier', $res1['vehicle_model']);
        $this->assertSame('Ambos lados', $res1['side']);

        // Caso 2: BRAND dice "Malibu" y MODEL está vacío
        $payload2 = [
            'id' => 'MLM829543589',
            'title' => 'Compresor A/a Malibu 13-16 2.0 Turbo',
            'price' => 2300.0,
            'available_quantity' => 1,
            'status' => 'active',
            'attributes' => [
                ['id' => 'BRAND', 'value_name' => 'Malibu'],
                ['id' => 'PART_NUMBER', 'value_name' => 'CAA20'],
            ],
        ];

        $res2 = $mapper->map($payload2)['attributes'];
        $this->assertSame('Chevrolet', $res2['vehicle_brand']);
        $this->assertSame('Malibu', $res2['vehicle_model']);
        $this->assertSame(2013, $res2['year_start']);
        $this->assertSame(2016, $res2['year_end']);

        // Caso 3: BRAND dice "Genérica", MODEL dice "2019"
        $payload3 = [
            'id' => 'MLM833986647',
            'title' => 'Faro Trasero Izquierdo Chev Aveo 96-99',
            'price' => 700.0,
            'available_quantity' => 1,
            'status' => 'active',
            'attributes' => [
                ['id' => 'BRAND', 'value_name' => 'Genérica'],
                ['id' => 'MODEL', 'value_name' => '2019'],
                ['id' => 'OEM', 'value_name' => 'OEM-AVEO-96'],
            ],
        ];

        $res3 = $mapper->map($payload3)['attributes'];
        $this->assertSame('Chevrolet', $res3['vehicle_brand']);
        $this->assertSame('Aveo', $res3['vehicle_model']);
        $this->assertSame(1996, $res3['year_start']);
        $this->assertSame(1999, $res3['year_end']);
        $this->assertSame('Izquierdo', $res3['side']);
        $this->assertSame('Trasero', $res3['position']);
        $this->assertSame('OEM-AVEO-96', $res3['oem_number']);
    }

    public function test_mapper_assigns_the_ten_official_categories_and_ml_subcategory(): void
    {
        $mapper = new MercadoLibreProductMapper();

        $cases = [
            [
                ['id' => 'MLM1', 'title' => 'Faro Delantero Nissan Versa', 'domain_id' => 'MLM-VEHICLE_HEADLIGHTS'],
                'Iluminación',
                'Faros Delanteros',
            ],
            [
                ['id' => 'MLM2', 'title' => 'Salpicadera Spark', 'domain_id' => 'MLM-VEHICLE_FENDERS'],
                'Carrocería y Colisión',
                'Salpicaderas',
            ],
            [
                ['id' => 'MLM3', 'title' => 'Caliper Audi A1', 'domain_id' => 'MLM-VEHICLE_BRAKE_CALIPERS'],
                'Frenos',
                'Calipers',
            ],
            [
                ['id' => 'MLM4', 'title' => 'Amortiguador Tiguan', 'domain_id' => 'MLM-VEHICLE_SHOCK_ABSORBERS'],
                'Suspensión y Dirección',
                'Amortiguadores',
            ],
            [
                ['id' => 'MLM5', 'title' => 'Bobina Encendido BMW', 'domain_id' => 'MLM-VEHICLE_IGNITION_COILS'],
                'Eléctrico y Sensores',
                'Bobinas de Encendido',
            ],
            [
                ['id' => 'MLM6', 'title' => 'Cinturon Seguridad Mazda 2', 'domain_id' => 'MLM-VEHICLE_SEAT_BELTS'],
                'Interior y Seguridad',
                'Cinturones de Seguridad',
            ],
            [
                ['id' => 'MLM7', 'title' => 'Motor 3/4 1.6 Nissan Versa', 'category_id' => 'MLM187514'],
                'Motores',
                null,
            ],
            [
                ['id' => 'MLM8', 'title' => 'Transmision Automatica Passat', 'category_id' => 'MLM187515'],
                'Transmisiones',
                null,
            ],
            [
                ['id' => 'MLM9', 'title' => 'Compresor A/c Malibu', 'domain_id' => 'MLM-VEHICLE_AC_COMPRESSOR'],
                'Enfriamiento y Clima (A/C)',
                null,
            ],
            [
                ['id' => 'MLM10', 'title' => 'Cuerpo Aceleracion Versa', 'domain_id' => 'MLM-VEHICLE_THROTTLE_BODIES'],
                'Partes de Motor e Inyección',
                null,
            ],
        ];

        foreach ($cases as [$payload, $expectedCategory, $expectedSubcategory]) {
            $mapped = $mapper->map($payload)['attributes'];
            $this->assertSame($expectedCategory, $mapped['category'], "Falló categoría para {$payload['title']}");
            if ($expectedSubcategory !== null) {
                $this->assertSame($expectedSubcategory, $mapped['subcategory'], "Falló subcategoría para {$payload['title']}");
            }
        }
    }
}
