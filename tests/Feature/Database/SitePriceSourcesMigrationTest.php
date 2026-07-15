<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SitePriceSourcesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_price_sources_table_has_selection_and_config_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('site_price_sources', [
            'site_id', 'price_source_id', 'enabled', 'priority', 'config_json',
            'created_at', 'updated_at',
        ]));
    }
}
