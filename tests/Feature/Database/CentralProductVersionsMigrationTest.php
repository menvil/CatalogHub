<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CentralProductVersionsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_product_versions_table_tracks_version_history(): void
    {
        $this->assertTrue(Schema::hasTable('central_product_versions'));
        $this->assertTrue(Schema::hasColumns('central_product_versions', [
            'id',
            'central_product_id',
            'version',
            'changed_by_user_id',
            'change_type',
            'reason',
            'snapshot_json',
            'diff_json',
            'metadata_json',
            'created_at',
        ]));

        $indexes = collect(Schema::getIndexes('central_product_versions'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['central_product_id', 'version'],
        ));

        foreach ([['central_product_id', 'created_at'], ['change_type']] as $columns) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === $columns,
            ));
        }
    }
}
