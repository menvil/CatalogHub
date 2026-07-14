<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectionConflictsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_projection_conflicts_table_tracks_data_quality_issues(): void
    {
        $this->assertTrue(Schema::hasTable('projection_conflicts'));
        $this->assertTrue(Schema::hasColumns('projection_conflicts', [
            'id',
            'site_id',
            'locale',
            'entity_type',
            'entity_id',
            'conflict_type',
            'severity',
            'status',
            'message',
            'context_json',
            'first_seen_at',
            'last_seen_at',
            'resolved_at',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('projection_conflicts'));

        foreach ([
            ['site_id', 'status'],
            ['site_id', 'entity_type', 'entity_id'],
            ['conflict_type'],
            ['severity'],
        ] as $columns) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === $columns,
            ));
        }
    }
}
