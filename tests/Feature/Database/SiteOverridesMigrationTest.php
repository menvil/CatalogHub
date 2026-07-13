<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiteOverridesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_overrides_schema_has_locale_aware_unique_key(): void
    {
        $this->assertTrue(Schema::hasTable('site_overrides'));
        $this->assertTrue(Schema::hasColumns('site_overrides', ['id', 'site_id', 'entity_type', 'entity_id', 'field', 'locale_code', 'value_json', 'reason', 'status', 'created_at', 'updated_at']));
        $this->assertTrue(collect(Schema::getIndexes('site_overrides'))->contains(fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['site_id', 'entity_type', 'entity_id', 'field', 'locale_code']));
    }
}
