<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_registry', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable()->index();
            $table->json('supported_page_types_json');
            $table->json('required_features_json')->nullable();
            $table->json('config_schema_json')->nullable();
            $table->string('view_component')->nullable();
            $table->string('preview_image_path')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_registry');
    }
};
