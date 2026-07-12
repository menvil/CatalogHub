<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_asset_id')->constrained('media_assets')->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('role');
            $table->unsignedInteger('position')->default(0);
            $table->string('locale')->nullable();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->unsignedBigInteger('market_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('visibility')->default('global');
            $table->timestamps();

            $table->index(['entity_type', 'entity_id', 'role']);
            $table->index(['entity_type', 'entity_id', 'role', 'locale'], 'media_assignments_locale_index');
            $table->index(['entity_type', 'entity_id', 'role', 'site_id'], 'media_assignments_site_index');
            $table->index(['entity_type', 'entity_id', 'role', 'market_id'], 'media_assignments_market_index');
            $table->index(['media_asset_id']);
            $table->index(['position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assignments');
    }
};
