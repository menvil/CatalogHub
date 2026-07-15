<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_clicks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->foreignId('market_offer_id')->constrained()->restrictOnDelete();
            $table->foreignId('central_product_id')->nullable()->constrained('central_products')->nullOnDelete();
            $table->foreignId('merchant_id')->nullable()->constrained('market_merchants')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->char('ip_hash', 64)->nullable();
            $table->char('user_agent_hash', 64)->nullable();
            $table->timestamp('clicked_at')->index();

            $table->index(['site_id', 'clicked_at']);
            $table->index(['market_offer_id', 'clicked_at']);
            $table->index(['central_product_id', 'clicked_at']);
            $table->index(['merchant_id', 'clicked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_clicks');
    }
};
