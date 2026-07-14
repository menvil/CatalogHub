<?php

namespace Tests\Feature\Content;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContentItemsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_items_table_has_expected_schema_and_indexes(): void
    {
        $this->assertTrue(Schema::hasTable('content_items'));
        $this->assertTrue(Schema::hasColumns('content_items', [
            'id', 'site_id', 'type', 'status', 'published_at', 'archived_at',
            'created_by_user_id', 'updated_by_user_id', 'metadata',
            'created_at', 'updated_at', 'deleted_at',
        ]));

        $indexes = collect(Schema::getIndexes('content_items'));
        foreach ([
            ['site_id', 'type'],
            ['site_id', 'status'],
            ['site_id', 'published_at'],
        ] as $columns) {
            $this->assertTrue($indexes->contains(fn (array $index): bool => $index['columns'] === $columns));
        }
    }
}
