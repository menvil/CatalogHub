<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_product_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('central_product_id')->nullable()->constrained('central_products')->nullOnDelete();
            $table->string('external_product_id')->nullable();
            $table->string('external_sku')->nullable();
            $table->text('external_url')->nullable();
            $table->string('external_title')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['price_source_id', 'external_product_id']);
            $table->index(['price_source_id', 'external_sku']);
            $table->index('central_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_product_mappings');
    }
};
