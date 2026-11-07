<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use App\Services\PriceService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionAndPriceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_service_applies_direct_promotion(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::query()->where('category', 'Motores')->firstOrFail();
        $product->update(['price' => 10000.00]);

        Promotion::create([
            'name' => 'Descuento Motores 10%',
            'badge_label' => '10% OFF',
            'mode' => 'direct',
            'visible_discount_percentage' => 10.00,
            'real_discount_percentage' => 10.00,
            'scope_type' => 'category',
            'scope_value' => 'Motores',
            'is_active' => true,
        ]);

        $service = app(PriceService::class);

        $this->assertEquals(9000.00, $service->getUnitPrice($product));

        $details = $service->getPromotionDetails($product);
        $this->assertTrue($details['has_promotion']);
        $this->assertEquals(10000.00, $details['original_strike_price']);
        $this->assertEquals(9000.00, $details['final_price']);
    }

    public function test_price_service_applies_promotion_with_savings_calculation(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::query()->where('category', 'Transmisiones')->firstOrFail();
        $product->update(['price' => 10000.00]);

        // Campaña legal con 15% de descuento directo
        Promotion::create([
            'name' => 'Hot Sale Transmisiones',
            'badge_label' => 'HOT SALE',
            'mode' => 'direct',
            'visible_discount_percentage' => 15.00,
            'real_discount_percentage' => 15.00,
            'scope_type' => 'category',
            'scope_value' => 'Transmisiones',
            'is_active' => true,
        ]);

        $service = app(PriceService::class);

        // Descuento del 15% sobre 10,000 = 8,500
        $this->assertEquals(8500.00, $service->getUnitPrice($product));

        // Precio tachado es el precio base real (10,000)
        $details = $service->getPromotionDetails($product);
        $this->assertTrue($details['has_promotion']);
        $this->assertEquals(10000.00, $details['original_strike_price']);
        $this->assertEquals(8500.00, $details['final_price']);
        $this->assertEquals(1500.00, $details['savings_amount']);
    }

    public function test_price_service_applies_valid_coupon(): void
    {
        Coupon::create([
            'code' => 'JC10',
            'type' => 'percentage',
            'value' => 10.00,
            'min_order_amount' => 1000.00,
            'is_active' => true,
        ]);

        $service = app(PriceService::class);

        // Subtotal menor al mínimo
        $failResult = $service->applyCoupon('JC10', 500.00);
        $this->assertFalse($failResult['valid']);

        // Subtotal válido (10% de 2,000 = 200)
        $successResult = $service->applyCoupon('jc10', 2000.00);
        $this->assertTrue($successResult['valid']);
        $this->assertEquals(200.00, $successResult['discount']);
    }

    public function test_authenticated_user_can_access_promotions_admin(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.promotions.index'));
        $response->assertStatus(200);
    }
}
