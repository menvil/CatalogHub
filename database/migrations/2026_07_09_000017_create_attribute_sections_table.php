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
            $table->foreignId('parent_id')->nullable()->constrained('attribute_sections')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->string('display_style')->default('table');
            $table->boolean('is_collapsible')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['central_category_id', 'code']);
            $table->index(['central_category_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_sections');
    }
};
