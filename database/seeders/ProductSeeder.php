<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductPhoto;
use App\Services\PriceService;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function __construct(
        private readonly PriceService $priceService,
        private readonly StockService $stockService,
    ) {}

    public function run(): void
    {
        $photoPoolByCategory = [
            'Motores' => [
                'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?auto=format&fit=crop&w=800&q=80',
            ],
            'Transmisiones' => [
                'https://images.unsplash.com/photo-1530046339160-ce3e530c7d2f?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1580273916550-e323be2ae537?auto=format&fit=crop&w=800&q=80',
            ],
            'Partes Internas' => [
                'https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?auto=format&fit=crop&w=800&q=80',
            ],
            'Colision' => [
                'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=800&q=80',
            ],
            'Suspension' => [
                'https://images.unsplash.com/photo-1558981806-ec527fa84c39?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1530046339160-ce3e530c7d2f?auto=format&fit=crop&w=800&q=80',
            ],
            'Electrico' => [
                'https://images.unsplash.com/photo-1625047509168-a7026f36de04?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?auto=format&fit=crop&w=800&q=80',
            ],
        ];

        $items = [
            // NISSAN (6)
            ['sku' => 'JC-NIS-001', 'title' => 'Motor 3/4 1.6L HR16DE Nissan Versa Original', 'category' => 'Motores', 'brand' => 'Nissan', 'model' => 'Versa', 'ys' => 2015, 'ye' => 2019, 'cond' => 'A', 'price' => 18500.00, 'stock' => 2, 'w' => 85.0, 'h' => 65.0, 'wi' => 55.0, 'l' => 70.0, 'status' => 'published', 'featured' => true, 'desc' => 'Motor 3/4 1.6L HR16DE original retirado de unidad con bajo kilometraje. Compresión pareja verificada en banco. Garantía escrita de 30 días.'],
            ['sku' => 'JC-NIS-002', 'title' => 'Transmisión Estándar 6 Vel Nissan NP300 Frontier 2.5L', 'category' => 'Transmisiones', 'brand' => 'Nissan', 'model' => 'NP300 Frontier', 'ys' => 2016, 'ye' => 2022, 'cond' => 'A', 'price' => 22900.00, 'stock' => 1, 'w' => 62.0, 'h' => 45.0, 'wi' => 45.0, 'l' => 82.0, 'status' => 'published', 'featured' => true, 'desc' => 'Caja de velocidades manual de 6 cambios para Nissan NP300 gasolina 2.5L. Sin ruidos ni fugas, lista para montar.'],
            ['sku' => 'JC-NIS-003', 'title' => 'Cabeza de Motor Completa Nissan Sentra B17 1.8L MRA8', 'category' => 'Partes Internas', 'brand' => 'Nissan', 'model' => 'Sentra', 'ys' => 2013, 'ye' => 2019, 'cond' => 'A', 'price' => 6800.00, 'stock' => 3, 'w' => 18.5, 'h' => 22.0, 'wi' => 28.0, 'l' => 52.0, 'status' => 'published', 'featured' => false, 'desc' => 'Cabeza de motor original en medida estándar con árboles de levas y válvulas. Sin cepilladas previas. Garantía de 15 días.'],
            ['sku' => 'JC-NIS-004', 'title' => 'Faro Delantero Derecho LED Nissan Kicks Original', 'category' => 'Colision', 'brand' => 'Nissan', 'model' => 'Kicks', 'ys' => 2017, 'ye' => 2020, 'cond' => 'A', 'price' => 3450.00, 'stock' => 4, 'w' => 3.8, 'h' => 28.0, 'wi' => 32.0, 'l' => 64.0, 'status' => 'published', 'featured' => false, 'desc' => 'Faro lado copiloto original Nissan con todas sus patas de sujeción íntegras y mica transparente sin pulir.'],
            ['sku' => 'JC-NIS-005', 'title' => 'Puente de Suspensión Delantero Nissan March / Versa', 'category' => 'Suspension', 'brand' => 'Nissan', 'model' => 'March', 'ys' => 2014, 'ye' => 2021, 'cond' => 'B', 'price' => 2350.00, 'stock' => 5, 'w' => 16.0, 'h' => 20.0, 'wi' => 58.0, 'l' => 95.0, 'status' => 'published', 'featured' => false, 'desc' => 'Puente o cuna de motor original sin golpes ni reparaciones estructurales.'],
            ['sku' => 'JC-NIS-006', 'title' => 'Alternador Original Valeo 110A Nissan Urvan NV350 2.5L', 'category' => 'Electrico', 'brand' => 'Nissan', 'model' => 'Urvan NV350', 'ys' => 2014, 'ye' => 2021, 'cond' => 'A', 'price' => 2600.00, 'stock' => 3, 'w' => 5.4, 'h' => 20.0, 'wi' => 20.0, 'l' => 24.0, 'status' => 'published', 'featured' => false, 'desc' => 'Alternador original probado en banco de carga. Regulador original funcionando al 100%.'],

            // CHEVROLET (6)
            ['sku' => 'JC-CHE-001', 'title' => 'Motor Completo 5.3L V8 EcoTec3 Chevrolet Silverado 1500', 'category' => 'Motores', 'brand' => 'Chevrolet', 'model' => 'Silverado', 'ys' => 2014, 'ye' => 2018, 'cond' => 'A', 'price' => 46500.00, 'stock' => 1, 'w' => 165.0, 'h' => 78.0, 'wi' => 70.0, 'l' => 85.0, 'status' => 'published', 'featured' => true, 'desc' => 'Motor 5.3L V8 probado antes de desmontar. Excelente presión de aceite y compresión. Respaldo de 30 días por escrito.'],
            ['sku' => 'JC-CHE-002', 'title' => 'Transmisión Automática 6T30 Chevrolet Sonic / Cruze 1.8L', 'category' => 'Transmisiones', 'brand' => 'Chevrolet', 'model' => 'Aveo', 'ys' => 2018, 'ye' => 2022, 'cond' => 'A', 'price' => 16800.00, 'stock' => 2, 'w' => 68.0, 'h' => 48.0, 'wi' => 50.0, 'l' => 62.0, 'status' => 'published', 'featured' => false, 'desc' => 'Transmisión automática completa con turbina original. Revisada y garantizada por 30 días.'],
            ['sku' => 'JC-CHE-003', 'title' => 'Cigüeñal Original Estándar Chevrolet Beat / Spark 1.2L', 'category' => 'Partes Internas', 'brand' => 'Chevrolet', 'model' => 'Beat', 'ys' => 2018, 'ye' => 2021, 'cond' => 'A', 'price' => 3900.00, 'stock' => 2, 'w' => 11.2, 'h' => 18.0, 'wi' => 18.0, 'l' => 48.0, 'status' => 'published', 'featured' => false, 'desc' => 'Cigüeñal original en medida STD comprobado con micrómetro, listo para armar.'],
            ['sku' => 'JC-CHE-004', 'title' => 'Cofre Delantero Original Chevrolet Onix Sin Detalles', 'category' => 'Colision', 'brand' => 'Chevrolet', 'model' => 'Onix', 'ys' => 2021, 'ye' => 2023, 'cond' => 'A', 'price' => 4800.00, 'stock' => 2, 'w' => 14.0, 'h' => 15.0, 'wi' => 118.0, 'l' => 145.0, 'status' => 'published', 'featured' => false, 'desc' => 'Cofre original GM en lámina virgen sin pasta, listo para preparación de pintura.'],
            ['sku' => 'JC-CHE-005', 'title' => 'Caja de Dirección Electroasistida Chevrolet Trax Original', 'category' => 'Suspension', 'brand' => 'Chevrolet', 'model' => 'Trax', 'ys' => 2015, 'ye' => 2020, 'cond' => 'A', 'price' => 3650.00, 'stock' => 3, 'w' => 9.5, 'h' => 22.0, 'wi' => 26.0, 'l' => 115.0, 'status' => 'published', 'featured' => false, 'desc' => 'Cremallera de dirección completa con bieletas originales sin juego.'],
            ['sku' => 'JC-CHE-006', 'title' => 'Cuerpo de Aceleración Original Chevrolet Aveo NG 1.5L', 'category' => 'Electrico', 'brand' => 'Chevrolet', 'model' => 'Aveo', 'ys' => 2018, 'ye' => 2023, 'cond' => 'A', 'price' => 1850.00, 'stock' => 6, 'w' => 1.8, 'h' => 16.0, 'wi' => 16.0, 'l' => 18.0, 'status' => 'published', 'featured' => false, 'desc' => 'Cuerpo de aceleración electrónico original GM sellado de planta.'],

            // TOYOTA (6)
            ['sku' => 'JC-TOY-001', 'title' => 'Motor 3/4 2.7L 2TR-FE Toyota Hilux Gasolina Original', 'category' => 'Motores', 'brand' => 'Toyota', 'model' => 'Hilux', 'ys' => 2016, 'ye' => 2022, 'cond' => 'A', 'price' => 34900.00, 'stock' => 2, 'w' => 115.0, 'h' => 70.0, 'wi' => 58.0, 'l' => 76.0, 'status' => 'published', 'featured' => true, 'desc' => 'Motor 2TR-FE 2.7L original para Toyota Hilux. Probado con garantía escrita de 30 días naturales.'],
            ['sku' => 'JC-TOY-002', 'title' => 'Transmisión CVT Original Toyota Corolla 1.8L 2ZR', 'category' => 'Transmisiones', 'brand' => 'Toyota', 'model' => 'Corolla', 'ys' => 2015, 'ye' => 2019, 'cond' => 'A', 'price' => 21500.00, 'stock' => 1, 'w' => 74.0, 'h' => 50.0, 'wi' => 52.0, 'l' => 66.0, 'status' => 'published', 'featured' => false, 'desc' => 'Caja automática CVT original Toyota de bajo kilometraje con convertidor incluido.'],
            ['sku' => 'JC-TOY-003', 'title' => 'Par de Árboles de Levas Toyota Tacoma 3.5L V6', 'category' => 'Partes Internas', 'brand' => 'Toyota', 'model' => 'Tacoma', 'ys' => 2016, 'ye' => 2021, 'cond' => 'A', 'price' => 5400.00, 'stock' => 2, 'w' => 6.5, 'h' => 15.0, 'wi' => 20.0, 'l' => 55.0, 'status' => 'published', 'featured' => false, 'desc' => 'Árboles de admisión y escape originales sin desgaste en levas ni muñones.'],
            ['sku' => 'JC-TOY-004', 'title' => 'Puerta Delantera Izquierda Completa Toyota RAV4', 'category' => 'Colision', 'brand' => 'Toyota', 'model' => 'RAV4', 'ys' => 2019, 'ye' => 2022, 'cond' => 'B', 'price' => 6200.00, 'stock' => 2, 'w' => 22.0, 'h' => 20.0, 'wi' => 105.0, 'l' => 120.0, 'status' => 'published', 'featured' => true, 'desc' => 'Puerta lado chofer original en lámina limpia, incluye elevador y cristal original.'],
            ['sku' => 'JC-TOY-005', 'title' => 'Diferencial Trasero Completo Paso 41/10 Toyota Hilux', 'category' => 'Suspension', 'brand' => 'Toyota', 'model' => 'Hilux', 'ys' => 2016, 'ye' => 2021, 'cond' => 'A', 'price' => 14800.00, 'stock' => 1, 'w' => 78.0, 'h' => 38.0, 'wi' => 42.0, 'l' => 155.0, 'status' => 'published', 'featured' => false, 'desc' => 'Eje trasero completo original Toyota sin zumbidos, coronas y satélites impecables.'],
            ['sku' => 'JC-TOY-006', 'title' => 'Compresor de Aire Acondicionado Denso Toyota Yaris R', 'category' => 'Electrico', 'brand' => 'Toyota', 'model' => 'Yaris', 'ys' => 2016, 'ye' => 2020, 'cond' => 'A', 'price' => 2950.00, 'stock' => 4, 'w' => 6.0, 'h' => 22.0, 'wi' => 22.0, 'l' => 26.0, 'status' => 'published', 'featured' => false, 'desc' => 'Compresor original Denso con buena compresión y clutch magnético funcionando.'],

            // FORD (6)
            ['sku' => 'JC-FOR-001', 'title' => 'Motor 3/4 2.5L Duratec Ford Ranger Gasolina Original', 'category' => 'Motores', 'brand' => 'Ford', 'model' => 'Ranger', 'ys' => 2015, 'ye' => 2020, 'cond' => 'A', 'price' => 26400.00, 'stock' => 2, 'w' => 98.0, 'h' => 68.0, 'wi' => 55.0, 'l' => 72.0, 'status' => 'published', 'featured' => true, 'desc' => 'Motor 2.5L Duratec original en medida estándar. Garantía funcional por escrito de 30 días.'],
            ['sku' => 'JC-FOR-002', 'title' => 'Transmisión Automática 6R80 Ford F-150 / Lobo 5.0L 4x2', 'category' => 'Transmisiones', 'brand' => 'Ford', 'model' => 'F-150', 'ys' => 2015, 'ye' => 2018, 'cond' => 'A', 'price' => 24500.00, 'stock' => 1, 'w' => 88.0, 'h' => 54.0, 'wi' => 54.0, 'l' => 86.0, 'status' => 'published', 'featured' => false, 'desc' => 'Transmisión automática de 6 velocidades original FoMoCo con turbina.'],
            ['sku' => 'JC-FOR-003', 'title' => 'Cabeza de Motor Ford Figo 1.5L 3 Cilindros Dragon', 'category' => 'Partes Internas', 'brand' => 'Ford', 'model' => 'Figo', 'ys' => 2019, 'ye' => 2021, 'cond' => 'A', 'price' => 5900.00, 'stock' => 2, 'w' => 14.5, 'h' => 22.0, 'wi' => 26.0, 'l' => 45.0, 'status' => 'published', 'featured' => false, 'desc' => 'Cabeza de motor original para Ford Figo 3 cilindros verificada en plano y hermeticidad.'],
            ['sku' => 'JC-FOR-004', 'title' => 'Calavera Trasera Izquierda LED Ford Escape Original', 'category' => 'Colision', 'brand' => 'Ford', 'model' => 'Escape', 'ys' => 2017, 'ye' => 2019, 'cond' => 'A', 'price' => 2250.00, 'stock' => 3, 'w' => 2.4, 'h' => 24.0, 'wi' => 28.0, 'l' => 44.0, 'status' => 'published', 'featured' => false, 'desc' => 'Calavera exterior lado conductor original FoMoCo sin fisuras ni humedad.'],
            ['sku' => 'JC-FOR-005', 'title' => 'Mango y Maza Delantera Derecha Ford Ranger 4x2', 'category' => 'Suspension', 'brand' => 'Ford', 'model' => 'Ranger', 'ys' => 2016, 'ye' => 2021, 'cond' => 'B', 'price' => 2800.00, 'stock' => 4, 'w' => 12.0, 'h' => 25.0, 'wi' => 28.0, 'l' => 34.0, 'status' => 'published', 'featured' => false, 'desc' => 'Mango delantero lado copiloto con balero y sensor ABS funcionando.'],
            ['sku' => 'JC-FOR-006', 'title' => 'Marcha / Motor de Arranque Original Ford Fiesta 1.6L', 'category' => 'Electrico', 'brand' => 'Ford', 'model' => 'Fiesta', 'ys' => 2014, 'ye' => 2019, 'cond' => 'A', 'price' => 1450.00, 'stock' => 5, 'w' => 3.5, 'h' => 16.0, 'wi' => 18.0, 'l' => 24.0, 'status' => 'published', 'featured' => false, 'desc' => 'Marcha original FoMoCo probada con bendix y automático en perfecto estado.'],

            // HONDA (6)
            ['sku' => 'JC-HON-001', 'title' => 'Motor 3/4 2.4L K24W Honda CR-V Earth Dreams Original', 'category' => 'Motores', 'brand' => 'Honda', 'model' => 'CR-V', 'ys' => 2015, 'ye' => 2017, 'cond' => 'A', 'price' => 29800.00, 'stock' => 1, 'w' => 102.0, 'h' => 68.0, 'wi' => 56.0, 'l' => 72.0, 'status' => 'published', 'featured' => true, 'desc' => 'Motor 2.4L inyección directa Earth Dreams original Honda con garantía escrita de 30 días.'],
            ['sku' => 'JC-HON-002', 'title' => 'Transmisión CVT Original Honda Civic 2.0L Aspirado', 'category' => 'Transmisiones', 'brand' => 'Honda', 'model' => 'Civic', 'ys' => 2016, 'ye' => 2020, 'cond' => 'A', 'price' => 23800.00, 'stock' => 1, 'w' => 76.0, 'h' => 48.0, 'wi' => 50.0, 'l' => 65.0, 'status' => 'published', 'featured' => false, 'desc' => 'Transmisión CVT original Honda probada en marcha antes de su desmontaje.'],
            ['sku' => 'JC-HON-003', 'title' => 'Cabeza de Motor Completa Honda City / Fit 1.5L i-VTEC', 'category' => 'Partes Internas', 'brand' => 'Honda', 'model' => 'City', 'ys' => 2015, 'ye' => 2020, 'cond' => 'A', 'price' => 6400.00, 'stock' => 2, 'w' => 15.0, 'h' => 20.0, 'wi' => 25.0, 'l' => 48.0, 'status' => 'published', 'featured' => false, 'desc' => 'Cabeza original Honda 1.5L lista para instalar, incluye tren de balancines y árbol.'],
            ['sku' => 'JC-HON-004', 'title' => 'Fascia Delantera Original Honda HR-V Para Pintar', 'category' => 'Colision', 'brand' => 'Honda', 'model' => 'HR-V', 'ys' => 2016, 'ye' => 2018, 'cond' => 'B', 'price' => 2900.00, 'stock' => 3, 'w' => 5.5, 'h' => 45.0, 'wi' => 55.0, 'l' => 178.0, 'status' => 'published', 'featured' => false, 'desc' => 'Fascia delantera original plástica con guías laterales completas.'],
            ['sku' => 'JC-HON-005', 'title' => 'Par de Horquillas Delanteras Originales Honda Accord', 'category' => 'Suspension', 'brand' => 'Honda', 'model' => 'Accord', 'ys' => 2014, 'ye' => 2017, 'cond' => 'A', 'price' => 2700.00, 'stock' => 3, 'w' => 9.8, 'h' => 18.0, 'wi' => 38.0, 'l' => 48.0, 'status' => 'published', 'featured' => false, 'desc' => 'Juego de horquillas inferiores izquierda y derecha con bujes originales en buen estado.'],
            ['sku' => 'JC-HON-006', 'title' => 'Alternador Original Mitsubishi 135A Honda CR-V 1.5L Turbo', 'category' => 'Electrico', 'brand' => 'Honda', 'model' => 'CR-V', 'ys' => 2017, 'ye' => 2021, 'cond' => 'A', 'price' => 3200.00, 'stock' => 2, 'w' => 5.6, 'h' => 20.0, 'wi' => 20.0, 'l' => 24.0, 'status' => 'published', 'featured' => false, 'desc' => 'Alternador original verificado en banco de pruebas con garantía de 7 días.'],

            // VOLKSWAGEN (6)
            ['sku' => 'JC-VW-001', 'title' => 'Motor 3/4 1.4L TSI Turbo Volkswagen Jetta MK7 / Tiguan', 'category' => 'Motores', 'brand' => 'Volkswagen', 'model' => 'Jetta', 'ys' => 2018, 'ye' => 2022, 'cond' => 'A', 'price' => 31500.00, 'stock' => 2, 'w' => 88.0, 'h' => 64.0, 'wi' => 52.0, 'l' => 68.0, 'status' => 'published', 'featured' => true, 'desc' => 'Motor 1.4L TSI generación EA211 en excelente estado. Garantía escrita de 30 días.'],
            ['sku' => 'JC-VW-002', 'title' => 'Transmisión Tiptronic 6 Vel 09G Volkswagen Vento / Jetta', 'category' => 'Transmisiones', 'brand' => 'Volkswagen', 'model' => 'Vento', 'ys' => 2015, 'ye' => 2021, 'cond' => 'A', 'price' => 17900.00, 'stock' => 2, 'w' => 70.0, 'h' => 46.0, 'wi' => 48.0, 'l' => 60.0, 'status' => 'published', 'featured' => false, 'desc' => 'Caja automática Tiptronic 09G con cuerpo de válvulas y turbina original.'],
            ['sku' => 'JC-VW-003', 'title' => 'Cabeza de Motor Completa Volkswagen Saveiro / Gol 1.6L', 'category' => 'Partes Internas', 'brand' => 'Volkswagen', 'model' => 'Saveiro', 'ys' => 2015, 'ye' => 2022, 'cond' => 'A', 'price' => 5600.00, 'stock' => 3, 'w' => 14.0, 'h' => 20.0, 'wi' => 24.0, 'l' => 46.0, 'status' => 'published', 'featured' => false, 'desc' => 'Cabeza original VW 1.6L 8 válvulas con porta árbol en medida estándar.'],
            ['sku' => 'JC-VW-004', 'title' => 'Tapa de Cajuela Completa Volkswagen Virtus Original', 'category' => 'Colision', 'brand' => 'Volkswagen', 'model' => 'Virtus', 'ys' => 2020, 'ye' => 2023, 'cond' => 'A', 'price' => 4900.00, 'stock' => 2, 'w' => 13.5, 'h' => 22.0, 'wi' => 62.0, 'l' => 132.0, 'status' => 'published', 'featured' => false, 'desc' => 'Tapa de cajuela original sin golpes ni reparaciones previas.'],
            ['sku' => 'JC-VW-005', 'title' => 'Eje Trasero Completo Volkswagen Jetta MK6 Freno de Disco', 'category' => 'Suspension', 'brand' => 'Volkswagen', 'model' => 'Jetta', 'ys' => 2011, 'ye' => 2018, 'cond' => 'B', 'price' => 3800.00, 'stock' => 3, 'w' => 34.0, 'h' => 28.0, 'wi' => 45.0, 'l' => 142.0, 'status' => 'published', 'featured' => false, 'desc' => 'Eje rígido trasero original derecho e íntegro, listo para montar.'],
            ['sku' => 'JC-VW-006', 'title' => 'Módulo Electroventilador Doble Volkswagen Tiguan 1.4L TSI', 'category' => 'Electrico', 'brand' => 'Volkswagen', 'model' => 'Tiguan', 'ys' => 2018, 'ye' => 2022, 'cond' => 'A', 'price' => 3100.00, 'stock' => 1, 'w' => 5.2, 'h' => 14.0, 'wi' => 48.0, 'l' => 74.0, 'status' => 'draft', 'featured' => false, 'desc' => 'Tolva con motoventiladores originales VW en borrador de revisión de almacén.'],
        ];

        foreach ($items as $index => $item) {
            $slug = Str::slug($item['title'] . '-' . $item['sku']);

            $product = Product::query()->updateOrCreate(
                ['sku' => $item['sku']],
                [
                    'title' => $item['title'],
                    'slug' => $slug,
                    'category' => $item['category'],
                    'vehicle_brand' => $item['brand'],
                    'vehicle_model' => $item['model'],
                    'year_start' => $item['ys'],
                    'year_end' => $item['ye'],
                    'condition' => $item['cond'],
                    'description' => $item['desc'],
                    'weight_kg' => $item['w'],
                    'height_cm' => $item['h'],
                    'width_cm' => $item['wi'],
                    'length_cm' => $item['l'],
                    'status' => $item['status'],
                    'is_featured' => $item['featured'],
                ]
            );

            // Persistir precio y existencia exclusivamente mediante PriceService y StockService
            $this->priceService->setPrice($product, $item['price']);
            $this->stockService->setStock($product, $item['stock']);

            $pool = $photoPoolByCategory[$item['category']] ?? [
                'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?auto=format&fit=crop&w=800&q=80',
            ];
            $primaryPhotoUrl = $pool[$index % count($pool)];

            ProductPhoto::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'sort_order' => 1,
                ],
                [
                    'path' => $primaryPhotoUrl,
                    'alt_text' => "{$item['title']} — Vista principal",
                ]
            );

            ProductPhoto::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'sort_order' => 2,
                ],
                [
                    'path' => 'imagenes/banner/garantia2.jpg',
                    'alt_text' => "Certificado de Garantía Oficial para {$item['title']}",
                ]
            );
        }
    }
}
