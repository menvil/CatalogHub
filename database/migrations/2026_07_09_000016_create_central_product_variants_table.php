<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('central_product_id')
                ->constrained('central_products')
                ->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('sku')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('position')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_product_variants');
    }
};
