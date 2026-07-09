<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_display_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attribute_definition_id')->constrained('attribute_definitions')->cascadeOnDelete();
            $table->string('market_code', 16)->nullable();
            $table->string('locale', 16)->nullable();
            $table->foreignId('display_unit_id')->nullable()->constrained('measurement_units')->nullOnDelete();
            $table->unsignedTinyInteger('decimals')->nullable();
            $table->string('rounding_mode')->default('half_up');
            $table->string('suffix_style')->default('symbol');
            $table->timestamps();

            $table->unique(['attribute_definition_id', 'market_code', 'locale'], 'attr_display_rules_scope_unique');
            $table->index('display_unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_display_rules');
    }
};
