<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NOTA IMPORTANTE DE ARQUITECTURA (FASE A0 -> FASE B):
 * ----------------------------------------------------
 * Esta tabla `products` y sus columnas son PROVISIONALES exclusivamente para
 * el andamiaje visual y demostración de la Fase A0.
 * En la Fase B estos campos serán reemplazados por la estructura real de
 * publicaciones e inventario importado desde la API de Mercado Libre.
 * Evitar acoplar lógica de negocio fuera de StockService y PriceService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('sku', 64)->unique();
            $table->string('category', 80)->index();
            $table->string('vehicle_brand', 80)->index();
            $table->string('vehicle_model', 100)->index();
            $table->unsignedSmallInteger('year_start')->index();
            $table->unsignedSmallInteger('year_end')->index();
            $table->string('condition', 2)->default('A')->index(); // A, B o C
            $table->decimal('price', 10, 2)->default(0)->index();
            $table->unsignedInteger('stock')->default(0);
            $table->text('description')->nullable();
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->string('status', 20)->default('draft')->index(); // published | draft
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
