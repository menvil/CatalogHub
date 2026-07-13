<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_source_id')->constrained('import_sources')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('central_categories')->cascadeOnDelete();
            $table->string('raw_key');
            $table->string('normalized_raw_key');
            $table->foreignId('attribute_definition_id')->nullable();
            $table->decimal('confidence', 5, 4)->default(0);
            $table->string('status')->default('auto')->index();
            $table->string('mapping_type')->default('attribute');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['import_source_id', 'category_id', 'raw_key'], 'attribute_mappings_source_category_raw_unique');
            $table->index(
                ['import_source_id', 'category_id', 'normalized_raw_key'],
                'attribute_mappings_normalized_lookup'
            );
            $table->foreign(
                ['attribute_definition_id', 'category_id'],
                'attribute_mappings_definition_category_fk',
            )
                ->references(['id', 'central_category_id'])
                ->on('attribute_definitions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_mappings');
    }
};
