<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Campañas de Promoción (Directa o Estratégica con Descuento Percibido)
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('badge_label')->default('PROMOCIÓN'); // Ej: "HOT SALE", "BUEN FIN", "-20% OFF"
            $table->string('mode')->default('direct'); // 'direct' o 'perceived' (anclaje simulado)
            $table->decimal('visible_discount_percentage', 5, 2)->default(10.00); // Lo que ve el cliente (ej. 20%)
            $table->decimal('real_discount_percentage', 5, 2)->default(10.00);    // Lo que realmente descuenta (ej. 5%)
            
            // Alcance de la campaña
            $table->string('scope_type')->default('all'); // 'all', 'category', 'brand', 'custom'
            $table->string('scope_value')->nullable();   // Categoría o Marca aplicable
            $table->json('product_ids')->nullable();     // IDs de productos para selección manual

            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order')->default(1);
            $table->timestamps();
        });

        // 2. Cupones de Descuento (para canje en Carrito / Checkout)
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('type')->default('percentage'); // 'percentage' o 'fixed'
            $table->decimal('value', 10, 2);              // 10.00 (%) o 500.00 (MXN)
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(false); // Si se muestra en la sección pública de promociones
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('promotions');
    }
};
