<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MediaAssignmentsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_media_assignments_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('media_assignments'));
        $this->assertTrue(Schema::hasColumns('media_assignments', [
            'id',
            'media_asset_id',
            'entity_type',
            'entity_id',
            'role',
            'position',
            'locale',
            'site_id',
            'market_id',
            'is_primary',
            'visibility',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_media_assignments_has_context_indexes_without_site_or_market_foreign_keys(): void
    {
        $indexes = collect(Schema::getIndexes('media_assignments'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['entity_type', 'entity_id', 'role', 'locale']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['entity_type', 'entity_id', 'role', 'site_id']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['entity_type', 'entity_id', 'role', 'market_id']
        ));
    }
}
