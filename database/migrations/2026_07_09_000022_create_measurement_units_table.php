<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dimension_id')->constrained('measurement_dimensions')->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('symbol');
            $table->string('name');
            $table->string('system')->default('metric');
            $table->decimal('factor_to_canonical', 20, 10)->default(1);
            $table->decimal('offset_to_canonical', 20, 10)->default(0);
            $table->unsignedTinyInteger('precision_default')->default(2);
            $table->json('aliases_json')->nullable();
            $table->boolean('is_canonical')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['id', 'dimension_id']);
            $table->index(['dimension_id', 'is_canonical']);
            $table->index(['is_active', 'system']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_units');
    }
};
