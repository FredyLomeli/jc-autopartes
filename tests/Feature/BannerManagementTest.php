<?php

namespace Tests\Feature;

use App\Models\HomeBanner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_banners(): void
    {
        $user = User::factory()->create();

        HomeBanner::create([
            'title' => 'Banner Principal',
            'image_path' => 'banners/demo.jpg',
            'order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('admin.banners.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_banner_with_uploaded_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('banner_hero.jpg', 1920, 600);

        $response = $this->actingAs($user)->post(route('admin.banners.store'), [
            'title' => 'Todo pa tu máquina',
            'subtitle' => 'Autopartes garantizadas',
            'image' => $file,
            'order' => 1,
            'is_active' => true,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('home_banners', [
            'title' => 'Todo pa tu máquina',
            'order' => 1,
        ]);

        $banner = HomeBanner::first();
        Storage::disk('public')->assertExists($banner->image_path);
    }

    public function test_can_reorder_banners_with_arrows(): void
    {
        $user = User::factory()->create();

        $banner1 = HomeBanner::create([
            'title' => 'Banner Primero',
            'image_path' => 'banners/1.jpg',
            'order' => 1,
            'is_active' => true,
        ]);

        $banner2 = HomeBanner::create([
            'title' => 'Banner Segundo',
            'image_path' => 'banners/2.jpg',
            'order' => 2,
            'is_active' => true,
        ]);

        // Mover el banner 2 hacia arriba
        $response = $this->actingAs($user)->post(route('admin.banners.reorder', $banner2->id), [
            'direction' => 'up',
        ]);

        $response->assertSessionHas('success');

        $this->assertEquals(1, $banner2->fresh()->order);
        $this->assertEquals(2, $banner1->fresh()->order);
    }
}
