<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('central_product_id')->nullable()->constrained('central_products')->nullOnDelete();
            $table->foreignId('central_category_id')->nullable()->constrained('central_categories')->nullOnDelete();
            $table->string('operation');
            $table->string('status')->default('queued');
            $table->string('triggered_by')->default('system');
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('affected_count')->default(0);
            $table->text('error_message')->nullable();
            $table->json('context_json')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['operation', 'created_at']);
            $table->index('central_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
