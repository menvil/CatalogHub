<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiteFeaturesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_features_schema_and_unique_feature_key_per_site(): void
    {
        $this->assertTrue(Schema::hasTable('site_features'));
        $this->assertTrue(Schema::hasColumns('site_features', ['id', 'site_id', 'feature_key', 'is_enabled', 'config_json', 'created_at', 'updated_at']));

        $this->assertTrue(collect(Schema::getIndexes('site_features'))->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['site_id', 'feature_key']
        ));
    }
}
