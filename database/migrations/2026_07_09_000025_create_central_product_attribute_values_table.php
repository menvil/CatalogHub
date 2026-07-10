<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_product_attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('central_product_id')->constrained('central_products')->cascadeOnDelete();
            $table->foreignId('attribute_definition_id')->constrained('attribute_definitions')->cascadeOnDelete();
            $table->text('raw_value')->nullable();
            $table->string('value_type');
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 20, 6)->nullable();
            $table->boolean('value_bool')->nullable();
            $table->string('value_enum_code')->nullable();
            $table->json('value_json')->nullable();
            $table->decimal('value_min', 20, 6)->nullable();
            $table->decimal('value_max', 20, 6)->nullable();
            $table->string('source_unit')->nullable();
            $table->decimal('canonical_value', 20, 6)->nullable();
            $table->string('canonical_unit')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->string('source_type')->nullable();
            $table->string('source_id')->nullable();
            $table->json('source_reference')->nullable();
            $table->timestamps();

            $table->unique(['central_product_id', 'attribute_definition_id'], 'cpav_product_attribute_unique');
            $table->index('attribute_definition_id');
            $table->index('value_enum_code');
            $table->index(['canonical_unit', 'canonical_value']);
            $table->index('source_unit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_product_attribute_values');
    }
};
