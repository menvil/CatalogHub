<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duplicate_candidates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->foreignId('normalized_product_draft_id')
                ->constrained('normalized_product_drafts')
                ->cascadeOnDelete();
            $table->string('candidate_type');
            $table->unsignedBigInteger('candidate_id');
            $table->decimal('score', 5, 4);
            $table->json('reason_json');
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['normalized_product_draft_id', 'candidate_type', 'candidate_id'],
                'duplicate_candidates_draft_target_unique'
            );
            $table->index(['candidate_type', 'candidate_id']);
            $table->index(['import_batch_id', 'status']);
            $table->index(['normalized_product_draft_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duplicate_candidates');
    }
};
