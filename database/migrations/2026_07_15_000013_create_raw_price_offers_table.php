<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_price_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_source_sync_log_id')->nullable()
                ->constrained('price_source_sync_logs')->nullOnDelete();
            $table->string('external_product_id')->nullable();
            $table->string('external_sku')->nullable();
            $table->string('external_title')->nullable();
            $table->json('raw_payload_json');
            $table->json('normalized_payload_json')->nullable();
            $table->string('status')->default('fetched');
            $table->text('error_message')->nullable();
            $table->timestamp('fetched_at')->index();
            $table->timestamps();

            $table->index(['price_source_id', 'status']);
            $table->index('price_source_sync_log_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_price_offers');
    }
};
