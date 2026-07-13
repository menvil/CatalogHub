<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('central_product_id')->constrained('central_products')->restrictOnDelete();
            $table->string('visibility')->default('hidden');
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('position')->nullable();
            $table->string('published_version')->nullable();
            $table->json('settings_json')->nullable();
            $table->timestamps();
            $table->unique(['site_id', 'central_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_products');
    }
};
