<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class CategoryFoundationMigrationTest extends TestCase
{
    use DatabaseTruncation;

    protected function beforeTruncatingDatabase(): void
    {
        RefreshDatabaseState::$migrated = false;
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_additive_backfill_preserves_legacy_shapes_and_never_invents_attribution_then_reverses(): void
    {
        $migration = require database_path('migrations/2026_10_04_200000_add_category_foundation_revisions.php');
        $migration->down();
        $ids = [];
        foreach (['draft', 'reviewed', 'approved', 'archived'] as $state) {
            $ids[$state] = DB::table('central_categories')->insertGetId([
                'name' => $state, 'slug' => $state, 'status' => 'archived', 'schema_status' => $state,
                'position' => 7, 'created_at' => '2026-08-01 12:00:00', 'updated_at' => '2026-08-01 12:00:00',
            ]);
        }
        DB::table('central_categories')->where('id', $ids['draft'])->update(['parent_id' => $ids['draft']]);
        DB::table('central_categories')->where('id', $ids['reviewed'])->update(['parent_id' => $ids['approved']]);
        DB::table('central_categories')->where('id', $ids['approved'])->update(['parent_id' => $ids['reviewed']]);
        $before = DB::table('central_categories')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $migration->up();
        foreach (DB::table('central_categories')->orderBy('id')->get() as $index => $row) {
            self::assertEquals($before[$index], array_intersect_key((array) $row, $before[$index]));
            self::assertSame(1, (int) $row->schema_revision);
            self::assertSame(in_array($row->schema_status, ['reviewed', 'approved']) ? 1 : null, $row->schema_reviewed_revision === null ? null : (int) $row->schema_reviewed_revision);
            self::assertSame($row->schema_status === 'approved' ? 1 : null, $row->schema_approved_revision === null ? null : (int) $row->schema_approved_revision);
            foreach (['schema_reviewed_by_user_id', 'schema_reviewed_at', 'schema_approved_by_user_id', 'schema_approved_at'] as $field) {
                self::assertNull($row->$field);
            }
        }
        self::assertSame(5, DB::table('category_hierarchy_scopes')->count());
        self::assertSame(0, (int) DB::table('category_hierarchy_scopes')->where('scope_key', 'root')->value('revision'));
        self::assertSame(0, DB::table('audit_log_entries')->count());
        $migration->down();
        self::assertFalse(Schema::hasTable('category_hierarchy_scopes'));
        self::assertFalse(Schema::hasColumn('central_categories', 'schema_revision'));
        self::assertSame($before, DB::table('central_categories')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
        $migration->up();
    }
}
