<?php

namespace Tests\Feature\Database;

use App\Models\Imports\NormalizedProductDraft;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NormalizedProductDraftsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_normalized_product_drafts_table_with_review_payload(): void
    {
        $this->assertTrue(Schema::hasTable('normalized_product_drafts'));
        $this->assertTrue(Schema::hasColumns('normalized_product_drafts', [
            'id',
            'import_batch_id',
            'raw_product_id',
            'matched_central_product_id',
            'brand_id',
            'category_id',
            'title',
            'slug',
            'normalized_payload_json',
            'attributes_json',
            'media_json',
            'confidence',
            'status',
            'review_notes',
            'approved_by_user_id',
            'approved_at',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_normalized_draft_has_expected_relationships_and_unique_raw_product(): void
    {
        $foreignKeys = collect(Schema::getForeignKeys('normalized_product_drafts'));
        $indexes = collect(Schema::getIndexes('normalized_product_drafts'));

        foreach ([
            'import_batches' => 'import_batch_id',
            'raw_products' => 'raw_product_id',
            'central_products' => 'matched_central_product_id',
            'central_brands' => 'brand_id',
            'central_categories' => 'category_id',
            'users' => 'approved_by_user_id',
        ] as $table => $column) {
            $this->assertTrue($foreignKeys->contains(
                fn (array $foreignKey): bool => $foreignKey['columns'] === [$column]
                    && $foreignKey['foreign_table'] === $table
            ));
        }

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['raw_product_id']
        ));
    }

    public function test_database_rejects_confidence_outside_normalized_range(): void
    {
        $this->expectException(QueryException::class);

        NormalizedProductDraft::factory()->create(['confidence' => '1.0001']);
    }
}
