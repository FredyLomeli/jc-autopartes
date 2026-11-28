<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('dimensions_locked')->default(false)->after('length_cm');
            $table->boolean('dimensions_source_incomplete')->default(false)->after('dimensions_locked');

            $table->index('dimensions_locked', 'products_dimensions_locked_idx');
            $table->index('dimensions_source_incomplete', 'products_dimensions_incomplete_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_dimensions_locked_idx');
            $table->dropIndex('products_dimensions_incomplete_idx');
            $table->dropColumn(['dimensions_locked', 'dimensions_source_incomplete']);
        });
    }
};
