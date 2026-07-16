<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_conflicts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('central_product_id')->nullable()->constrained('central_products')->nullOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('field_path');
            $table->json('central_value_json')->nullable();
            $table->json('local_value_json')->nullable();
            $table->string('conflict_type');
            $table->string('status')->default('open');
            $table->string('resolution')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->index(['central_product_id', 'status']);
            $table->index('conflict_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_conflicts');
    }
};
