<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_merchants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('market_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('website_url')->nullable();
            $table->foreignId('logo_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('status')->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['market_id', 'slug']);
            $table->index(['market_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_merchants');
    }
};
