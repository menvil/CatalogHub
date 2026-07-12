<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_asset_id')->constrained('media_assets')->cascadeOnDelete();
            $table->string('variant_type');
            $table->string('locale')->nullable();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->unsignedBigInteger('market_id')->nullable();
            $table->string('disk');
            $table->string('path');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('format')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('quality')->nullable();
            $table->string('transform_hash')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamps();

            $table->index(['media_asset_id', 'variant_type']);
            $table->index(['media_asset_id', 'variant_type', 'locale', 'site_id', 'market_id'], 'media_variants_lookup_index');
            $table->index(['site_id', 'market_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_variants');
    }
};
