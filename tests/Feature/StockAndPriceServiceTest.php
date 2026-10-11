<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductPhoto;
use App\Models\User;
use App\Services\PriceService;
use App\Services\StockService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class StockAndPriceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_is_idempotent_and_creates_admin_and_36_products_with_photos(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@jcautopartes.mx',
        ]);
        $this->assertSame(1, User::query()->where('email', 'admin@jcautopartes.mx')->count());
        $this->assertSame(36, Product::query()->count());
        $this->assertSame(72, ProductPhoto::query()->count());

        // Ejecutar por segunda vez para verificar idempotencia
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'admin@jcautopartes.mx')->count());
        $this->assertSame(36, Product::query()->count());
        $this->assertSame(72, ProductPhoto::query()->count());
    }

    public function test_stock_service_reads_validates_clamps_and_updates_stock(): void
    {
        $this->seed(DatabaseSeeder::class);

        $stockService = app(StockService::class);
        $product = Product::query()->where('sku', 'JC-NIS-001')->firstOrFail();

        $this->assertSame(2, $stockService->getAvailableStock($product));
        $this->assertTrue($stockService->hasStock($product, 1));
        $this->assertTrue($stockService->hasStock($product, 2));
        $this->assertFalse($stockService->hasStock($product, 3));
        $this->assertFalse($stockService->hasStock($product, 0));

        $this->assertSame(2, $stockService->clampQuantity($product, 5));
        $this->assertSame(1, $stockService->clampQuantity($product, 1));
        $this->assertSame(0, $stockService->clampQuantity($product, 0));

        $stockService->setStock($product, 7);
        $this->assertSame(7, $stockService->getAvailableStock($product->id));

        $this->expectException(InvalidArgumentException::class);
        $stockService->setStock($product, -1);
    }

    public function test_price_service_reads_calculates_formats_and_updates_price(): void
    {
        $this->seed(DatabaseSeeder::class);

        $priceService = app(PriceService::class);
        $product = Product::query()->where('sku', 'JC-NIS-001')->firstOrFail();

        $this->assertSame(18500.00, $priceService->getUnitPrice($product));
        $this->assertSame(37000.00, $priceService->calculateSubtotal($product, 2));
        $this->assertSame('$18,500.00 MXN', $priceService->format($priceService->getUnitPrice($product)));

        $priceService->setPrice($product, 19250.50);
        $this->assertSame(19250.50, $priceService->getUnitPrice($product->id));

        $this->expectException(InvalidArgumentException::class);
        $priceService->setPrice($product, -10.00);
    }
}
