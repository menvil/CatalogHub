<?php

namespace Tests\Feature\Categories;

use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryHierarchyScope;
use App\Models\CentralCatalog\CentralCategory;
use App\Queries\Categories\CategoryHierarchyDiagnosticsQuery;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class CategoryDiagnosticsTest extends TestCase
{
    use DatabaseTruncation;

    protected function beforeTruncatingDatabase(): void
    {
        RefreshDatabaseState::$migrated = false;
    }

    protected function tearDown(): void
    {
        $this->truncateTablesForAllConnections();
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_corrupt_hierarchy_is_detected_deterministically_without_repair_or_audit(): void
    {
        $a = CentralCategory::factory()->create(['position' => 7, 'status' => 'archived', 'schema_status' => 'approved']);
        $b = CentralCategory::factory()->create(['position' => 7]);
        $c = CentralCategory::factory()->create();
        $c->update(['parent_id' => $c->id]);
        $d = CentralCategory::factory()->create(['parent_id' => $a->id]);
        $e = CentralCategory::factory()->create(['parent_id' => $d->id]);
        $d->update(['parent_id' => $e->id]);
        $section = AttributeSection::factory()->for($a, 'category')->create();
        // Reproduce legacy corruption without privileged or database-specific FK disabling.
        Schema::table('central_categories', fn (Blueprint $table) => $table->dropForeign(['parent_id']));
        $orphanId = null;
        try {
            $missingParentId = $e->id + 1000;
            $orphan = CentralCategory::factory()->create(['parent_id' => $missingParentId]);
            $orphanId = $orphan->id;
            CentralCategory::factory()->create(['parent_id' => $orphanId]);
            $before = CentralCategory::query()->orderBy('id')->get()->toJson();
            $query = app(CategoryHierarchyDiagnosticsQuery::class);
            $report = $query->report();
            self::assertSame($report, $query->report());
            self::assertSame([$a->id, $b->id], $report['root_ids']);
            self::assertSame([$a->id], $report['archived_ids']);
            self::assertContains('self_parent', array_column($report['issues'], 'code'));
            self::assertContains('cycle', array_column($report['issues'], 'code'));
            self::assertContains('non_contiguous_positions', array_column($report['issues'], 'code'));
            self::assertSame([
                ['code' => 'missing_parent', 'category_id' => $orphanId, 'parent_id' => $missingParentId],
            ], array_values(array_filter($report['issues'], fn (array $issue) => $issue['code'] === 'missing_parent')));
            $this->artisan('catalog:diagnose-category-hierarchy')->assertExitCode(1);
            self::assertSame($before, CentralCategory::query()->orderBy('id')->get()->toJson());
            self::assertSame(0, AuditLogEntry::query()->count());
        } finally {
            if ($orphanId !== null) {
                DB::table('central_categories')->where('id', $orphanId)->update(['parent_id' => null]);
            }
            Schema::table('central_categories', fn (Blueprint $table) => $table->foreign('parent_id')->references('id')->on('central_categories')->nullOnDelete());
        }
    }

    public function test_empty_or_valid_hierarchy_reports_no_issues_and_does_not_create_scopes(): void
    {
        $this->artisan('catalog:diagnose-category-hierarchy')->assertSuccessful();
        $a = CentralCategory::factory()->create(['position' => 0]);
        CentralCategory::factory()->create(['position' => 1]);
        CentralCategory::factory()->create(['parent_id' => $a->id, 'position' => 0]);
        self::assertSame([], app(CategoryHierarchyDiagnosticsQuery::class)->report()['issues']);
        $this->artisan('catalog:diagnose-category-hierarchy')->assertSuccessful();
        self::assertSame(0, AuditLogEntry::query()->count());
        self::assertSame(1, CategoryHierarchyScope::query()->count());
    }
}
