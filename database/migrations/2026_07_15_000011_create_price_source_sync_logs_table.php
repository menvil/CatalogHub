<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_source_sync_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('price_source_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('queued');
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('items_fetched')->default(0);
            $table->unsignedInteger('items_normalized')->default(0);
            $table->unsignedInteger('items_matched')->default(0);
            $table->unsignedInteger('items_updated')->default(0);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['price_source_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_source_sync_logs');
    }
};
