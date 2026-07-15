<?php

namespace Tests\Feature\Content;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContentTranslationsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_translations_table_has_expected_schema(): void
    {
        $this->assertTrue(Schema::hasTable('content_translations'));
        $this->assertTrue(Schema::hasColumns('content_translations', [
            'id', 'content_item_id', 'locale', 'slug', 'title', 'excerpt', 'body', 'body_json',
            'status', 'meta_title', 'meta_description', 'og_title', 'og_description',
            'source_hash', 'created_at', 'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('content_translations'));
        $this->assertTrue($indexes->contains(fn (array $index): bool => $index['unique'] === true
            && $index['columns'] === ['content_item_id', 'locale']));
        $this->assertTrue($indexes->contains(fn (array $index): bool => $index['columns'] === ['locale', 'slug', 'status']));
    }
}
