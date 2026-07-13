<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ThemeManifestsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_theme_manifests_table_has_required_columns_and_constraints(): void
    {
        $this->assertTrue(Schema::hasTable('theme_manifests'));
        $this->assertTrue(Schema::hasColumns('theme_manifests', [
            'id',
            'theme_id',
            'manifest_json',
            'supports_json',
            'layouts_json',
            'schema_version',
            'validated_at',
            'validation_errors_json',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('theme_manifests'));
        $foreignKeys = collect(Schema::getForeignKeys('theme_manifests'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['theme_id']
        ));
        $this->assertTrue($foreignKeys->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['theme_id'] && $foreignKey['foreign_table'] === 'themes'
        ));
    }

    public function test_manifest_json_can_be_persisted(): void
    {
        $themeId = DB::table('themes')->insertGetId([
            'code' => 'catalog_clean',
            'name' => 'Catalog Clean',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $manifest = ['code' => 'catalog_clean', 'supports' => ['hero_search'], 'layouts' => ['home' => 'home-clean']];

        DB::table('theme_manifests')->insert([
            'theme_id' => $themeId,
            'manifest_json' => json_encode($manifest, JSON_THROW_ON_ERROR),
            'supports_json' => json_encode($manifest['supports'], JSON_THROW_ON_ERROR),
            'layouts_json' => json_encode($manifest['layouts'], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('theme_manifests', ['theme_id' => $themeId]);
    }
}
