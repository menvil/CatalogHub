<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_unit_preferences', function (Blueprint $table): void {
            $table->id();
            $table->string('market_code', 16);
            $table->foreignId('dimension_id')->constrained('measurement_dimensions')->cascadeOnDelete();
            $table->foreignId('preferred_unit_id')->constrained('measurement_units')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['market_code', 'dimension_id']);
            $table->index('preferred_unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_unit_preferences');
    }
};
