<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_attribute_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('central_category_id')->constrained('central_categories')->restrictOnDelete();
            $table->foreignId('attribute_definition_id')->constrained('attribute_definitions')->restrictOnDelete();
            $table->foreignId('attribute_section_id')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_sortable')->default(false);
            $table->timestamps();
            $table->unique(['central_category_id', 'attribute_definition_id'], 'category_attribute_assignment_unique');
            $table->unique(['id', 'central_category_id'], 'assignment_identity_category_unique');
            $table->foreign(['attribute_section_id', 'central_category_id'], 'assignment_section_category_fk')
                ->references(['id', 'central_category_id'])->on('attribute_sections')->restrictOnDelete();
            $table->index(['central_category_id', 'attribute_section_id', 'position'], 'assignment_category_section_position_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_attribute_assignments');
    }
};
