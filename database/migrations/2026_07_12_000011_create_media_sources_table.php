<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_asset_id')->unique()->constrained('media_assets')->cascadeOnDelete();
            $table->string('source_type')->nullable();
            $table->text('source_url')->nullable();
            $table->string('source_name')->nullable();
            $table->string('license_type')->nullable();
            $table->text('license_url')->nullable();
            $table->text('attribution')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['media_asset_id', 'source_type']);
            $table->index(['license_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_sources');
    }
};
