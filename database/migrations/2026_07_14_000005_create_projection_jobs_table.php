<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projection_jobs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('job_type');
            $table->string('status')->default('pending')->index();
            $table->nullableMorphs('target');
            $table->string('locale', 20)->nullable();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload_json')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projection_jobs');
    }
};
