<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80);
            // Unicode case folding can expand an otherwise valid 80-character label.
            $table->string('normalized_name');
            $table->char('normalized_name_hash', 64)->unique();
            $table->timestamps();
        });

        Schema::create('central_brand_tag', function (Blueprint $table): void {
            $table->foreignId('central_brand_id')->constrained('central_brands')->cascadeOnDelete();
            $table->foreignId('catalog_tag_id')->constrained('catalog_tags')->cascadeOnDelete();
            $table->primary(['central_brand_id', 'catalog_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_brand_tag');
        Schema::dropIfExists('catalog_tags');
    }
};
