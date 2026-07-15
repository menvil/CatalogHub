<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('market_id')->constrained()->restrictOnDelete();
            $table->foreignId('market_merchant_id')->constrained()->restrictOnDelete();
            $table->foreignId('central_product_id')->constrained('central_products')->restrictOnDelete();
            $table->foreignId('price_source_id')->constrained()->restrictOnDelete();
            $table->foreignId('external_product_mapping_id')->nullable()
                ->constrained('external_product_mappings')->nullOnDelete();
            $table->decimal('price', 12, 2);
            $table->char('currency', 3);
            $table->decimal('original_price', 12, 2)->nullable();
            $table->char('original_currency', 3)->nullable();
            $table->string('availability')->default('unknown');
            $table->string('condition')->default('unknown');
            $table->decimal('delivery_price', 12, 2)->nullable();
            $table->string('delivery_time')->nullable();
            $table->text('url')->nullable();
            $table->timestamp('last_seen_at')->index();
            $table->timestamp('last_checked_at');
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['market_merchant_id', 'central_product_id', 'price_source_id'],
                'market_offers_current_unique',
            );
            $table->index(['market_id', 'status']);
            $table->index(['central_product_id', 'status']);
            $table->index(['price_source_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_offers');
    }
};
