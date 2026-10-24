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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 32)->unique();
            $table->string('customer_name', 255);
            $table->string('customer_email', 255)->index();
            $table->string('customer_phone', 32);
            $table->string('delivery_method', 32)->default('shipping'); // shipping | pickup

            // Datos de entrega
            $table->string('shipping_address', 255)->nullable();
            $table->string('shipping_neighborhood', 255)->nullable();
            $table->string('shipping_city', 120)->nullable();
            $table->string('shipping_state', 120)->nullable();
            $table->string('shipping_postal_code', 10)->nullable()->index();
            $table->text('shipping_notes')->nullable();

            // Importes monetarios
            $table->decimal('items_subtotal', 10, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('MXN');

            // Estados de compra y logística
            $table->string('payment_status', 32)->default('pending_payment')->index(); // pending_payment, paid, failed, refunded
            $table->string('order_status', 32)->default('pending')->index(); // pending, preparing, shipped, delivered, cancelled

            // Datos de la pasarela (Mercado Pago)
            $table->string('payment_gateway', 32)->default('mercadopago');
            $table->string('mercadopago_preference_id', 128)->nullable()->index();
            $table->string('mercadopago_payment_id', 64)->nullable()->index();
            $table->string('mercadopago_payment_type', 64)->nullable();
            $table->string('mercadopago_merchant_order_id', 64)->nullable();

            // Trazabilidad
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            // Snapshots históricos inmutables
            $table->string('product_title', 255);
            $table->string('product_sku', 64);
            $table->string('part_number', 64)->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('subtotal', 10, 2);
            $table->string('photo_url', 500)->nullable();
            $table->unsignedInteger('warranty_days')->default(7);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
