<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projection_conflicts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 20)->nullable();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('conflict_type');
            $table->string('severity');
            $table->string('status')->default('open');
            $table->text('message')->nullable();
            $table->json('context_json')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->index(['site_id', 'entity_type', 'entity_id']);
            $table->index('conflict_type');
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projection_conflicts');
    }
};
