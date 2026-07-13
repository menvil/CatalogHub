<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('central_category_id')->constrained('central_categories')->cascadeOnDelete();
            $table->foreignId('attribute_section_id')->nullable();
            $table->string('code');
            $table->string('name');
            $table->string('data_type')->default('string');
            $table->string('dimension')->nullable();
            $table->string('canonical_unit')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_sortable')->default(false);
            $table->boolean('is_comparable')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_searchable')->default(false);
            $table->timestamps();

            $table->unique(['central_category_id', 'code']);
            $table->unique(['id', 'central_category_id'], 'attribute_definitions_id_category_unique');
            $table->index(['central_category_id', 'attribute_section_id', 'position']);
            $table->index(['central_category_id', 'is_filterable']);
            $table->index(['central_category_id', 'is_comparable']);
            $table->foreign(['attribute_section_id', 'central_category_id'], 'attr_def_section_cat_fk')
                ->references(['id', 'central_category_id'])
                ->on('attribute_sections')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_definitions');
    }
};
