<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiteProductsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_products_schema_and_unique_product_per_site(): void
    {
        $this->assertTrue(Schema::hasTable('site_products'));
        $this->assertTrue(Schema::hasColumns('site_products', ['id', 'site_id', 'central_product_id', 'visibility', 'is_featured', 'position', 'published_version', 'settings_json', 'created_at', 'updated_at']));
        $this->assertTrue(collect(Schema::getIndexes('site_products'))->contains(fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['site_id', 'central_product_id']));
    }
}
