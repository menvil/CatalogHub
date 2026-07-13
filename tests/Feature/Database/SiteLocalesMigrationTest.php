<?php

namespace Tests\Feature\Database;

use App\Models\Locale;
use App\Models\Site;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiteLocalesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_site_locales_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('site_locales'));
        $this->assertTrue(Schema::hasColumns('site_locales', [
            'id', 'site_id', 'locale_code', 'is_default', 'is_enabled', 'position', 'created_at', 'updated_at',
        ]));
    }

    public function test_site_locale_is_unique_per_site(): void
    {
        $indexes = collect(Schema::getIndexes('site_locales'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['site_id', 'locale_code']
        ));
    }

    public function test_locale_code_references_the_locale_catalog(): void
    {
        $foreignKeys = collect(Schema::getForeignKeys('site_locales'));

        $this->assertTrue($foreignKeys->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['locale_code']
                && $foreignKey['foreign_table'] === 'locales'
                && $foreignKey['foreign_columns'] === ['code']
        ));

        $this->expectException(QueryException::class);
        DB::table('site_locales')->insert([
            'site_id' => Site::factory()->create()->id,
            'locale_code' => 'unknown-locale',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_only_one_default_locale_is_allowed_per_site(): void
    {
        $site = Site::factory()->create();
        Locale::factory()->create(['code' => 'de-DE']);
        Locale::factory()->create(['code' => 'en-DE']);

        DB::table('site_locales')->insert([
            'site_id' => $site->id,
            'locale_code' => 'de-DE',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        DB::table('site_locales')->insert([
            'site_id' => $site->id,
            'locale_code' => 'en-DE',
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
