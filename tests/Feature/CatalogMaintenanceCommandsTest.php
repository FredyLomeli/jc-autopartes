<?php

namespace Tests\Feature;

use App\Models\MarketplaceRawItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogMaintenanceCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_purge_seeded_command_respects_dry_run_and_protects_ordered_items(): void
    {
        $this->seed(DatabaseSeeder::class);

        $orderedSeeded = Product::query()->whereNull('mercadolibre_id')->firstOrFail();
        $order = Order::query()->create([
            'order_number' => 'JC-2026-00099',
            'customer_name' => 'Cliente Prueba',
            'customer_email' => 'cliente@example.com',
            'customer_phone' => '3781234567',
            'delivery_method' => Order::DELIVERY_PICKUP,
            'items_subtotal' => 1000,
            'shipping_cost' => 0,
            'total' => 1000,
            'currency' => 'MXN',
            'payment_status' => Order::PAYMENT_PAID,
            'order_status' => Order::STATUS_PREPARING,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $orderedSeeded->id,
            'product_title' => $orderedSeeded->title,
            'product_sku' => $orderedSeeded->sku,
            'unit_price' => 1000,
            'quantity' => 1,
            'subtotal' => 1000,
        ]);

        // Modo dry-run no debe borrar nada
        $this->artisan('catalog:purge-seeded')
            ->assertExitCode(0)
            ->expectsOutputToContain('Modo simulación');
        $this->assertSame(36, Product::query()->whereNull('mercadolibre_id')->count());

        // Modo --force borra los 35 libres y protege el 1 referenciado en order_items
        $this->artisan('catalog:purge-seeded --force')
            ->assertExitCode(0)
            ->expectsOutputToContain('Se eliminaron 35 artículos de prueba');
        $this->assertSame(1, Product::query()->whereNull('mercadolibre_id')->count());
        $this->assertDatabaseHas('products', ['id' => $orderedSeeded->id]);
    }

    public function test_meli_sync_catalog_from_raw_processes_in_chunks_and_updates_existing_products(): void
    {
        MarketplaceRawItem::query()->create([
            'external_id' => 'MLM9990001',
            'marketplace' => 'mercadolibre',
            'title' => 'Espejo Lateral Rh Ford Escape 01-07',
            'status' => 'active',
            'fetched_at' => now(),
            'raw_payload' => [
                'id' => 'MLM9990001',
                'title' => 'Espejo Lateral Rh Ford Escape 01-07',
                'price' => 850,
                'available_quantity' => 2,
                'status' => 'active',
                'attributes' => [
                    ['id' => 'BRAND', 'value_name' => 'Genérica'],
                    ['id' => 'SELLER_SKU', 'value_name' => 'FOESC01-249'],
                    ['id' => 'SELLER_PACKAGE_HEIGHT', 'value_name' => '25 cm'],
                    ['id' => 'SELLER_PACKAGE_WIDTH', 'value_name' => '20 cm'],
                    ['id' => 'SELLER_PACKAGE_LENGTH', 'value_name' => '30 cm'],
                    ['id' => 'SELLER_PACKAGE_WEIGHT', 'value_name' => '1800 g'],
                ],
            ],
        ]);

        $this->artisan('meli:sync-catalog --from-raw')
            ->assertExitCode(0)
            ->expectsOutputToContain('Catálogo sincronizado con éxito');

        $product = Product::query()->where('mercadolibre_id', 'MLM9990001')->firstOrFail();
        $this->assertSame('Ford', $product->vehicle_brand);
        $this->assertSame('Escape', $product->vehicle_model);
        $this->assertSame(2001, $product->year_start);
        $this->assertSame(2007, $product->year_end);
        $this->assertSame('Derecho', $product->side);
        $this->assertSame('FOESC01-249', $product->sku);
        $this->assertEquals(1.8, (float) $product->weight_kg);

        // Segunda corrida actualiza sin duplicar
        $this->artisan('meli:sync-catalog --from-raw')
            ->assertExitCode(0);
        $this->assertSame(1, Product::query()->where('mercadolibre_id', 'MLM9990001')->count());
    }
}
