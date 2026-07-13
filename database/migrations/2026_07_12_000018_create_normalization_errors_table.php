<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('normalization_errors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->foreignId('raw_product_id')->nullable()->constrained('raw_products')->nullOnDelete();
            $table->foreignId('normalized_product_draft_id')
                ->nullable()
                ->constrained('normalized_product_drafts')
                ->nullOnDelete();
            $table->string('severity')->default('error')->index();
            $table->string('code')->index();
            $table->text('message');
            $table->string('raw_key')->nullable();
            $table->text('raw_value')->nullable();
            $table->json('context_json')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['import_batch_id', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('normalization_errors');
    }
};
