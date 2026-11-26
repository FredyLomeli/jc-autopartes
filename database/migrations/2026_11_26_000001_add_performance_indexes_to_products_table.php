<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'is_featured', 'id'], 'products_status_featured_id_idx');
            $table->index(['status', 'category', 'id'], 'products_status_category_id_idx');
            $table->index(['status', 'vehicle_brand', 'vehicle_model'], 'products_status_brand_model_idx');
            $table->index(['status', 'price', 'id'], 'products_status_price_id_idx');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                'ALTER TABLE products ADD FULLTEXT INDEX products_search_fulltext (title, sku, part_number, vehicle_brand, vehicle_model)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE products DROP INDEX products_search_fulltext');
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_status_featured_id_idx');
            $table->dropIndex('products_status_category_id_idx');
            $table->dropIndex('products_status_brand_model_idx');
            $table->dropIndex('products_status_price_id_idx');
        });
    }
};
