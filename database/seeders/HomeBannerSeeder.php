<?php

namespace Database\Seeders;

use App\Models\HomeBanner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class HomeBannerSeeder extends Seeder
{
    /**
     * Crea 2 banners de ejemplo para el carrusel si aún no existen registros.
     */
    public function run(): void
    {
        if (HomeBanner::query()->exists()) {
            return;
        }

        $samples = [
            [
                'source' => public_path('imagenes/banner/hero-bg.jpg'),
                'target' => 'banners/demo-hero.jpg',
                'title' => 'Autopartes Originales con Garantía Escrita',
                'subtitle' => 'Motores, transmisiones, colisión y eléctrica probados en almacén desde Tepatitlán para todo México.',
                'button_text' => 'Explorar Catálogo',
                'button_url' => '/refacciones',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'source' => public_path('imagenes/banner/envio.jpg'),
                'target' => 'banners/demo-envio.jpg',
                'title' => 'Envíos Seguros a Domicilio y Servicio Ocurre',
                'subtitle' => 'Cotiza con tu Código Postal y recibe tu refacción por paquetería asegurada en 2 a 5 días hábiles.',
                'button_text' => 'Ver Garantía Oficial',
                'button_url' => '/garantia',
                'order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($samples as $sample) {
            if (File::exists($sample['source']) && ! Storage::disk('public')->exists($sample['target'])) {
                Storage::disk('public')->put($sample['target'], File::get($sample['source']));
            }

            HomeBanner::query()->create([
                'title' => $sample['title'],
                'subtitle' => $sample['subtitle'],
                'image_path' => $sample['target'],
                'button_text' => $sample['button_text'],
                'button_url' => $sample['button_url'],
                'order' => $sample['order'],
                'is_active' => $sample['is_active'],
            ]);
        }
    }
}
