<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layout_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('theme_id')->constrained('themes')->cascadeOnDelete();
            $table->string('page_type')->index();
            $table->string('code');
            $table->string('name');
            $table->string('view_path');
            $table->json('slots_json')->nullable();
            $table->json('config_schema_json')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();

            $table->unique(['theme_id', 'page_type', 'code'], 'layout_templates_theme_page_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layout_templates');
    }
};
