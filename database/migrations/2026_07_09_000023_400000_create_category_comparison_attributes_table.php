<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_comparison_attributes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('central_category_id')->constrained('central_categories')->restrictOnDelete();
            $table->foreignId('category_attribute_assignment_id');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->unique(['central_category_id', 'category_attribute_assignment_id'], 'comparison_category_assignment_unique');
            $table->foreign(['category_attribute_assignment_id', 'central_category_id'], 'comparison_assignment_category_fk')
                ->references(['id', 'central_category_id'])->on('category_attribute_assignments')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_comparison_attributes');
    }
};
