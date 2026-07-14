<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectionLogsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_projection_logs_table_tracks_build_events(): void
    {
        $this->assertTrue(Schema::hasTable('projection_logs'));
        $this->assertTrue(Schema::hasColumns('projection_logs', [
            'id',
            'projection_job_id',
            'site_id',
            'level',
            'event',
            'message',
            'context_json',
            'entity_type',
            'entity_id',
            'created_at',
        ]));

        $indexes = collect(Schema::getIndexes('projection_logs'));

        foreach ([
            ['projection_job_id'],
            ['site_id', 'level'],
            ['site_id', 'event'],
            ['entity_type', 'entity_id'],
            ['created_at'],
        ] as $columns) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === $columns,
            ));
        }
    }
}
