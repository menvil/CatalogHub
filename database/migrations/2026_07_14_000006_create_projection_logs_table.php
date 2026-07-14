<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projection_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('projection_job_id')->nullable()->constrained('projection_jobs')->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('level');
            $table->string('event');
            $table->text('message')->nullable();
            $table->json('context_json')->nullable();
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('projection_job_id');
            $table->index(['site_id', 'level']);
            $table->index(['site_id', 'event']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projection_logs');
    }
};
