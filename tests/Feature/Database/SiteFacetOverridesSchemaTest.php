<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiteFacetOverridesSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_facet_overrides_table_has_expected_schema(): void
    {
        $this->assertTrue(Schema::hasTable('site_facet_overrides'));
        $this->assertTrue(Schema::hasColumns('site_facet_overrides', [
            'id',
            'site_id',
            'facet_definition_id',
            'label_override',
            'position_override',
            'is_visible',
            'default_collapsed',
            'config_json',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('site_facet_overrides'));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['site_id', 'facet_definition_id'],
        ));
    }
}
