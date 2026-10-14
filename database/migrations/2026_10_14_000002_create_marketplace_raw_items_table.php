<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_raw_items', function (Blueprint $table) {
            $table->id();
            $table->string('marketplace')->default('mercadolibre')->index();
            $table->string('external_id')->unique();
            $table->string('title')->nullable();
            $table->string('status')->default('active')->index();
            $table->json('raw_payload');
            $table->timestamp('fetched_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_raw_items');
    }
};
