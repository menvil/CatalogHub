<?php

namespace Tests\Feature\Categories;

use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryHierarchyScope;
use App\Models\CentralCatalog\CentralCategory;
use App\Queries\Categories\CategoryHierarchyDiagnosticsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CategoryDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_shapes_are_detected_deterministically_without_repair_or_audit(): void
    {
        $a = CentralCategory::factory()->create(['position' => 7, 'status' => 'archived', 'schema_status' => 'approved']);
        $b = CentralCategory::factory()->create(['position' => 7]);
        $c = CentralCategory::factory()->create();
        $c->update(['parent_id' => $c->id]);
        $d = CentralCategory::factory()->create(['parent_id' => $a->id]);
        $e = CentralCategory::factory()->create(['parent_id' => $d->id]);
        $d->update(['parent_id' => $e->id]);
        $section = AttributeSection::factory()->for($a, 'category')->create();
        $nested = AttributeSection::factory()->for($a, 'category')->for($section, 'parent')->create();
        $before = CentralCategory::query()->orderBy('id')->get()->toJson();
        $query = app(CategoryHierarchyDiagnosticsQuery::class);
        $report = $query->report();
        self::assertSame($report, $query->report());
        self::assertSame([$a->id, $b->id], $report['root_ids']);
        self::assertSame([$a->id], $report['archived_ids']);
        self::assertSame([$nested->id], $report['nested_section_ids']);
        self::assertContains('self_parent', array_column($report['issues'], 'code'));
        self::assertContains('cycle', array_column($report['issues'], 'code'));
        self::assertContains('non_contiguous_positions', array_column($report['issues'], 'code'));
        $this->artisan('catalog:diagnose-category-hierarchy')->assertExitCode(1);
        self::assertSame($before, CentralCategory::query()->orderBy('id')->get()->toJson());
        self::assertSame(0, AuditLogEntry::query()->count());
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
