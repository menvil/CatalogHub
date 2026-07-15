<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_price_sources', function (Blueprint $table): void {
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_source_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('priority')->nullable();
            $table->json('config_json')->nullable();
            $table->timestamps();

            $table->primary(['site_id', 'price_source_id']);
            $table->index(['site_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_price_sources');
    }
};
