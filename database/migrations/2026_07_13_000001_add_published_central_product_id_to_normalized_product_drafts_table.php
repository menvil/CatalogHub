<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('normalized_product_drafts', function (Blueprint $table): void {
            $table->foreignId('published_central_product_id')
                ->nullable()
                ->after('matched_central_product_id')
                ->constrained('central_products')
                ->nullOnDelete();
        });

        if (DB::getDriverName() === 'sqlite') {
            $this->restoreSqliteConfidenceTriggers();
        }
    }

    public function down(): void
    {
        Schema::table('normalized_product_drafts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('published_central_product_id');
        });

        if (DB::getDriverName() === 'sqlite') {
            $this->restoreSqliteConfidenceTriggers();
        }
    }

    private function restoreSqliteConfidenceTriggers(): void
    {
        // SQLite rebuilds the table when changing its foreign keys, dropping its triggers.
        DB::unprepared('DROP TRIGGER IF EXISTS normalized_product_drafts_confidence_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS normalized_product_drafts_confidence_update');
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
    }
};
