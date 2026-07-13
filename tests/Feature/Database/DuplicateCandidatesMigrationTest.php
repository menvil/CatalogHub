<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DuplicateCandidatesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_duplicate_candidates_table_with_score_reason_and_review_fields(): void
    {
        $this->assertTrue(Schema::hasTable('duplicate_candidates'));
        $this->assertTrue(Schema::hasColumns('duplicate_candidates', [
            'id',
            'import_batch_id',
            'normalized_product_draft_id',
            'candidate_type',
            'candidate_id',
            'score',
            'reason_json',
            'status',
            'reviewed_by_user_id',
            'reviewed_at',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_duplicate_candidate_has_review_relationships_and_unique_target_per_draft(): void
    {
        $foreignKeys = collect(Schema::getForeignKeys('duplicate_candidates'));
        $indexes = collect(Schema::getIndexes('duplicate_candidates'));

        foreach ([
            'import_batches' => 'import_batch_id',
            'normalized_product_drafts' => 'normalized_product_draft_id',
            'users' => 'reviewed_by_user_id',
        ] as $table => $column) {
            $this->assertTrue($foreignKeys->contains(
                fn (array $foreignKey): bool => $foreignKey['columns'] === [$column]
                    && $foreignKey['foreign_table'] === $table
            ));
        }

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === [
                    'normalized_product_draft_id',
                    'candidate_type',
                    'candidate_id',
                ]
        ));
    }
}
