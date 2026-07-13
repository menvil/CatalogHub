<?php

namespace Tests\Feature\Database;

use App\Models\Site;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $localeColumn = collect(Schema::getColumns('site_overrides'))->firstWhere('name', 'locale_code');
        $this->assertFalse($localeColumn['nullable']);
    }

    public function test_global_override_scope_is_unique(): void
    {
        $site = Site::factory()->create();
        $attributes = [
            'site_id' => $site->id,
            'entity_type' => 'product',
            'entity_id' => 42,
            'field' => 'local_title',
            'locale_code' => '',
            'value_json' => json_encode(['value' => 'Title'], JSON_THROW_ON_ERROR),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('site_overrides')->insert($attributes);

        $this->expectException(QueryException::class);
        DB::table('site_overrides')->insert($attributes);
    }
}
