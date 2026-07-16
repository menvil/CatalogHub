<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('central_product_id')->nullable()->constrained('central_products')->nullOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('field_path');
            $table->json('old_value_json')->nullable();
            $table->json('proposed_value_json');
            $table->string('evidence_url')->nullable();
            $table->text('evidence_note')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('applied_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['site_id', 'status']);
            $table->index(['central_product_id', 'status']);
            $table->index('created_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_change_requests');
    }
};
