<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('market_id')->constrained()->restrictOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->string('status')->default('inactive')->index();
            $table->json('config_json')->nullable();
            $table->string('update_frequency')->nullable();
            $table->timestamp('last_sync_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['market_id', 'code']);
            $table->index(['market_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_sources');
    }
};
