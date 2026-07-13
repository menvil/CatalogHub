<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('normalized_product_drafts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->foreignId('raw_product_id')->unique()->constrained('raw_products')->cascadeOnDelete();
            $table->foreignId('matched_central_product_id')
                ->nullable()
                ->constrained('central_products')
                ->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('central_brands')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('central_categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->json('normalized_payload_json');
            $table->json('attributes_json');
            $table->json('media_json');
            $table->decimal('confidence', 5, 4)->default(0);
            $table->string('status')->default('pending_review')->index();
            $table->text('review_notes')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['import_batch_id', 'status']);
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER normalized_product_drafts_confidence_insert
                BEFORE INSERT ON normalized_product_drafts
                WHEN NEW.confidence < 0 OR NEW.confidence > 1
                BEGIN
                    SELECT RAISE(ABORT, 'normalized_product_drafts confidence must be between 0 and 1');
                END
                SQL);
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER normalized_product_drafts_confidence_update
                BEFORE UPDATE OF confidence ON normalized_product_drafts
                WHEN NEW.confidence < 0 OR NEW.confidence > 1
                BEGIN
                    SELECT RAISE(ABORT, 'normalized_product_drafts confidence must be between 0 and 1');
                END
                SQL);
        } else {
            DB::statement(<<<'SQL'
                ALTER TABLE normalized_product_drafts
                ADD CONSTRAINT normalized_product_drafts_confidence_check
                CHECK (confidence >= 0 AND confidence <= 1)
                SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('normalized_product_drafts');
    }
};
