<?php

namespace Tests\Feature\Content;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContentRelationsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_relations_table_has_polymorphic_schema_and_indexes(): void
    {
        $this->assertTrue(Schema::hasTable('content_relations'));
        $this->assertTrue(Schema::hasColumns('content_relations', [
            'id', 'content_item_id', 'related_type', 'related_id', 'relation_type',
            'position', 'metadata', 'created_at', 'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('content_relations'));
        $this->assertTrue($indexes->contains(fn (array $index): bool => $index['columns'] === ['related_type', 'related_id']));
        $this->assertTrue($indexes->contains(fn (array $index): bool => $index['unique'] === true
            && $index['columns'] === ['content_item_id', 'related_type', 'related_id']));
    }
}
