<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectionJobsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_projection_jobs_table_tracks_rebuild_requests(): void
    {
        $this->assertTrue(Schema::hasTable('projection_jobs'));
        $this->assertTrue(Schema::hasColumns('projection_jobs', [
            'id',
            'uuid',
            'site_id',
            'job_type',
            'status',
            'target_type',
            'target_id',
            'locale',
            'requested_by_user_id',
            'payload_json',
            'attempts',
            'started_at',
            'finished_at',
            'failed_at',
            'failure_reason',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('projection_jobs'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['status'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['site_id', 'status'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['target_type', 'target_id'],
        ));
    }
}
