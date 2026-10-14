<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('mercadolibre_id', 32)->nullable()->unique()->after('id');
            $table->string('mercadolibre_permalink', 500)->nullable()->after('mercadolibre_id');
            $table->string('part_number', 64)->nullable()->index()->after('category');
            $table->string('oem_number', 64)->nullable()->after('part_number');
            $table->string('side', 32)->nullable()->after('year_end');
            $table->string('position', 32)->nullable()->after('side');
            $table->json('raw_attributes')->nullable()->after('is_featured');
            $table->timestamp('last_synced_at')->nullable()->after('raw_attributes');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['mercadolibre_id']);
            $table->dropIndex(['part_number']);
            $table->dropColumn([
                'mercadolibre_id',
                'mercadolibre_permalink',
                'part_number',
                'oem_number',
                'side',
                'position',
                'raw_attributes',
                'last_synced_at',
            ]);
        });
    }
};
