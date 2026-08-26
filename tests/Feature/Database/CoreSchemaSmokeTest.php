<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class CoreSchemaSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_core_tables_and_identity_columns_exist(): void
    {
        $tables = [
            'sites' => ['id', 'market_id', 'code', 'domain', 'status'],
            'users' => ['id', 'site_id', 'email', 'role'],
            'central_products' => ['id', 'central_brand_id', 'central_category_id', 'slug', 'status'],
            'central_brands' => ['id', 'slug', 'status', 'country_id', 'founded_year', 'support_url', 'contact_email', 'primary_color'],
            'catalog_tags' => ['id', 'name', 'normalized_name', 'normalized_name_hash'],
            'central_brand_tag' => ['central_brand_id', 'catalog_tag_id'],
            'central_categories' => ['id', 'parent_id', 'slug', 'status', 'schema_status'],
            'locales' => ['id', 'code', 'language_code', 'is_active', 'is_default'],
            'markets' => ['id', 'code', 'currency_code', 'default_locale', 'status'],
            'media_assets' => ['id', 'uuid', 'disk', 'original_path', 'status'],
            'countries' => ['id', 'alpha2', 'alpha3', 'numeric_code', 'canonical_name', 'is_active'],
            'country_translations' => ['id', 'country_id', 'locale', 'name'],
        ];

        foreach ($tables as $table => $columns) {
            self::assertTrue(Schema::hasTable($table), "Missing required core table: {$table}.");
            self::assertTrue(Schema::hasColumns($table, $columns), "Missing required columns in {$table}.");
        }
    }
}
