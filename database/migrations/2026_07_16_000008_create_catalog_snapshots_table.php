<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('status')->default('pending');
            $table->string('snapshot_type')->default('full');
            $table->string('storage_disk')->default('local');
            $table->string('storage_path')->nullable();
            $table->json('files_json')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['snapshot_type', 'created_at']);
            $table->index('created_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_snapshots');
    }
};
