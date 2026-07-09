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
            $table->foreignId('preferred_unit_id');
            $table->timestamps();

            $table->unique(['market_code', 'dimension_id']);
            $table->foreign(['preferred_unit_id', 'dimension_id'], 'market_pref_unit_dimension_fk')
                ->references(['id', 'dimension_id'])
                ->on('measurement_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_unit_preferences');
    }
};
