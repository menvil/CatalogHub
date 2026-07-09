<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('central_category_id')->constrained('central_categories')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable();
            $table->string('code');
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->string('display_style')->default('table');
            $table->boolean('is_collapsible')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['central_category_id', 'code']);
            $table->unique(['id', 'central_category_id']);
            $table->index(['central_category_id', 'position']);
            $table->foreign(['parent_id', 'central_category_id'], 'attr_section_parent_cat_fk')
                ->references(['id', 'central_category_id'])
                ->on('attribute_sections')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_sections');
    }
};
