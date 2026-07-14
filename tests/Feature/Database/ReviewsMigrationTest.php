<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReviewsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviews_table_has_required_columns_and_indexes(): void
    {
        $this->assertTrue(Schema::hasTable('reviews'));
        $this->assertTrue(Schema::hasColumns('reviews', [
            'id',
            'site_id',
            'central_product_id',
            'author_name',
            'author_email',
            'rating',
            'pros',
            'cons',
            'comment',
            'status',
            'locale',
            'approved_at',
            'rejected_at',
            'spam_marked_at',
            'metadata',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('reviews'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['site_id', 'central_product_id', 'status'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['site_id', 'status'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['created_at'],
        ));
    }
}
