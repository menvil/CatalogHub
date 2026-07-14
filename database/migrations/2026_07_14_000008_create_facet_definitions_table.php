<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facet_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('central_categories')->cascadeOnDelete();
            $table->foreignId('attribute_definition_id')->nullable()->constrained('attribute_definitions')->nullOnDelete();
            $table->string('code')->index();
            $table->string('label_override')->nullable();
            $table->string('facet_type');
            $table->string('source_type');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_collapsible')->default(true);
            $table->boolean('default_collapsed')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->json('config_json')->nullable();
            $table->timestamps();

            $table->index('category_id');
            $table->index('attribute_definition_id');
            $table->unique(['category_id', 'code']);
            $table->index(['category_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facet_definitions');
    }
};
