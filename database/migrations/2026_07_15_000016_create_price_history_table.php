<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('market_offer_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->char('currency', 3);
            $table->string('availability');
            $table->string('condition')->nullable();
            $table->decimal('delivery_price', 12, 2)->nullable();
            $table->timestamp('checked_at')->index();
            $table->json('source_snapshot_json')->nullable();
            $table->timestamps();

            $table->index(['market_offer_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_history');
    }
};
