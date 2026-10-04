<?php

namespace Tests\Feature\Categories;

use App\Actions\CentralCatalog\CreateCentralCategoryAction;
use App\Actions\CentralCatalog\ReorderCentralCategoriesAction;
use App\Actions\CentralCatalog\ReparentCentralCategoryAction;
use App\Actions\CentralCatalog\SaveLegacyCentralCategoryAction;
use App\Actions\CentralCatalog\UpdateCentralCategoryAction;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CategoryHierarchyScope;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Categories\CategoryHierarchy;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

final class CategoryHierarchyActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actor = User::factory()->centralAdmin()->create();
    }

    private function create(string $slug, ?int $parentId = null): CentralCategory
    {
        return app(CreateCentralCategoryAction::class)->handle($this->actor,
            ['name' => $slug, 'slug' => $slug, 'parent_id' => $parentId],
            app(CategoryHierarchy::class)->revision($parentId));
    }

    public function test_creates_root_and_child_with_draft_lifecycle_and_atomic_append_revisions(): void
    {
        $a = $this->create('a');
        $b = $this->create('b');
        $c = $this->create('child', $a->id);
        $d = $this->create('child-two', $a->id);
        self::assertSame([0, 1, 0, 1], [$a->position, $b->position, $c->position, $d->position]);
        self::assertSame('draft', $c->status->value);
        self::assertSame(1, $c->schema_revision);
        self::assertSame(2, app(CategoryHierarchy::class)->revision(null));
        self::assertSame(2, app(CategoryHierarchy::class)->revision($a->id));
        self::assertSame(4, AuditLogEntry::query()->where('action', 'catalog.category.created')->count());
        self::assertTrue(CategoryHierarchyScope::query()->whereKey('parent:'.$d->id)->exists());
    }

    public function test_reparents_between_parents_compacts_old_and_appends_new(): void
    {
        $a = $this->create('a');
        $b = $this->create('b');
        $c = $this->create('c', $a->id);
        $d = $this->create('d', $a->id);
        $e = $this->create('e', $b->id);
        app(ReparentCentralCategoryAction::class)->handle($this->actor, $c, $b->id, 2, 1);
        self::assertSame(0, $d->fresh()->position);
        self::assertSame($b->id, $c->fresh()->parent_id);
        self::assertSame(1, $c->fresh()->position);
        self::assertSame(0, $e->fresh()->position);
        self::assertSame(3, app(CategoryHierarchy::class)->revision($a->id));
        self::assertSame(2, app(CategoryHierarchy::class)->revision($b->id));
        $event = AuditLogEntry::query()->where('action', 'catalog.category.reparented')->sole();
        self::assertSame($a->id, $event->before_json['parent_id']);
        self::assertSame([$e->id, $c->id], $event->after_json['ordered_ids']);
    }

    public function test_reparents_child_to_root_and_rejects_stale_parent_snapshot(): void
    {
        $a = $this->create('a');
        $c = $this->create('c', $a->id);
        app(ReparentCentralCategoryAction::class)->handle($this->actor, $c, null, 1, 1);
        self::assertNull($c->fresh()->parent_id);
        self::assertSame(1, $c->fresh()->position);
        $this->expectException(ValidationException::class);
        app(ReparentCentralCategoryAction::class)->handle($this->actor, $c, $a->id, 1, 1);
    }

    public function test_self_descendant_and_deep_cycle_attempts_are_rejected_without_writes(): void
    {
        $a = $this->create('a');
        $b = $this->create('b', $a->id);
        $c = $this->create('c', $b->id);
        $d = $this->create('d', $c->id);
        $before = CentralCategory::query()->orderBy('id')->get()->toJson();
        $events = AuditLogEntry::query()->count();
        foreach ([$a->id, $b->id, $d->id, 999999] as $parentId) {
            try {
                app(ReparentCentralCategoryAction::class)->handle($this->actor, $a, $parentId, 1, app(CategoryHierarchy::class)->revision($parentId));
                self::fail('Unsafe reparent succeeded');
            } catch (ValidationException) {
                self::assertSame($before, CentralCategory::query()->orderBy('id')->get()->toJson());
                self::assertSame($events, AuditLogEntry::query()->count());
                self::assertSame(1, app(CategoryHierarchy::class)->revision(null));
            }
        }
    }

    public function test_complete_set_root_reorder_repairs_legacy_positions_explicitly(): void
    {
        $a = CentralCategory::factory()->create(['position' => 5]);
        $b = CentralCategory::factory()->create(['position' => 5]);
        $scope = app(ReorderCentralCategoriesAction::class)->handle($this->actor, null, [$b->id, $a->id], 0);
        self::assertSame(1, $scope->revision);
        self::assertSame([0, 1], [$b->fresh()->position, $a->fresh()->position]);
        self::assertSame([$b->id, $a->id], AuditLogEntry::query()->sole()->after_json['ordered_ids']);
        app(ReorderCentralCategoriesAction::class)->handle($this->actor, null, [$b->id, $a->id], 1);
        self::assertSame(1, AuditLogEntry::query()->count());
        self::assertSame(1, app(CategoryHierarchy::class)->revision(null));
    }

    public static function invalidOrders(): array
    {
        return ['missing' => [[0]], 'duplicate' => [[0, 0]], 'foreign' => [[0, 2]], 'unknown' => [[0, 999]], 'stale' => [[1, 0], 0]];
    }

    #[DataProvider('invalidOrders')]
    public function test_rejects_invalid_sibling_sets_or_stale_revisions(array $indices, int $revision = 2): void
    {
        $a = $this->create('a');
        $b = $this->create('b');
        $c = $this->create('c', $a->id);
        $ids = [$a->id, $b->id, $c->id];
        $order = array_map(fn ($i) => $ids[$i] ?? 999999, $indices);
        $events = AuditLogEntry::query()->count();
        try {
            app(ReorderCentralCategoriesAction::class)->handle($this->actor, null, $order, $revision);
            self::fail('Invalid order succeeded');
        } catch (ValidationException) {
            self::assertSame([0, 1], [$a->fresh()->position, $b->fresh()->position]);
            self::assertSame(2, app(CategoryHierarchy::class)->revision(null));
            self::assertSame($events, AuditLogEntry::query()->count());
        }
    }

    public function test_stale_create_and_reparent_are_atomic_and_invalid_legacy_ancestry_is_rejected(): void
    {
        $a = $this->create('a');
        $b = $this->create('b');
        foreach (['create', 'reparent'] as $op) {
            try {
                if ($op === 'create') {
                    app(CreateCentralCategoryAction::class)->handle($this->actor, ['name' => 'c', 'slug' => 'c'], 0);
                } else {
                    app(ReparentCentralCategoryAction::class)->handle($this->actor, $a, $b->id, 0, 0);
                }
                self::fail('Stale write succeeded');
            } catch (ValidationException) {
                self::assertSame(2, CentralCategory::query()->count());
                self::assertSame(2, AuditLogEntry::query()->count());
            }
        }
        $b->update(['parent_id' => $b->id]);
        $this->expectException(ValidationException::class);
        $this->create('bad-child', $b->id);
    }

    public function test_identity_update_is_no_op_safe_and_cannot_smuggle_hierarchy_or_status(): void
    {
        $a = $this->create('a');
        $before = $a->updated_at;
        $this->travel(5)->seconds();
        app(UpdateCentralCategoryAction::class)->handle($this->actor, $a, ['name' => 'a', 'slug' => 'a']);
        self::assertSame(1, AuditLogEntry::query()->count());
        self::assertEquals($before, $a->fresh()->updated_at);
        app(UpdateCentralCategoryAction::class)->handle($this->actor, $a, ['name' => 'A', 'slug' => 'a-new']);
        self::assertSame(['name', 'slug'], AuditLogEntry::query()->where('action', 'catalog.category.updated')->sole()->after_json['changed_fields']);
        foreach (['parent_id' => $a->id, 'position' => 2, 'status' => 'active', 'schema_status' => 'approved'] as $field => $value) {
            try {
                app(UpdateCentralCategoryAction::class)->handle($this->actor, $a, ['name' => 'A', 'slug' => 'a-new', $field => $value]);
                self::fail('Generic update bypassed explicit action');
            } catch (ValidationException) {
                self::assertSame(2, AuditLogEntry::query()->count());
            }
        }
    }

    public function test_create_rejects_duplicate_slug_and_ambiguous_legacy_order(): void
    {
        $a = $this->create('a');
        foreach (['a', 'new'] as $slug) {
            if ($slug === 'new') {
                $a->update(['position' => 8]);
            }
            try {
                $this->create($slug);
                self::fail('Invalid creation succeeded');
            } catch (ValidationException) {
                self::assertSame(1, CentralCategory::query()->count());
                self::assertSame(1, AuditLogEntry::query()->count());
            }
        }
    }

    public function test_audit_failure_rolls_back_reparent_revisions_positions_and_scope_creation(): void
    {
        $a = $this->create('a');
        $b = $this->create('b');
        $c = $this->create('c', $a->id);
        $audit = Mockery::mock(AuditRecorder::class);
        $audit->shouldReceive('record')->once()->andThrow(new RuntimeException('audit unavailable'));
        $this->app->instance(AuditRecorder::class, $audit);
        try {
            app(ReparentCentralCategoryAction::class)->handle($this->actor, $c, $b->id, 1, 0);
            self::fail('Expected audit failure');
        } catch (RuntimeException) {
            self::assertSame($a->id, $c->fresh()->parent_id);
            self::assertSame(0, $c->fresh()->position);
            self::assertSame(1, app(CategoryHierarchy::class)->revision($a->id));
            self::assertSame(0, app(CategoryHierarchy::class)->revision($b->id));
        }
    }

    public function test_audit_failure_rolls_back_creation_and_identity_and_reorder(): void
    {
        $a = CentralCategory::factory()->create(['position' => 0]);
        $b = CentralCategory::factory()->create(['position' => 1]);
        $audit = Mockery::mock(AuditRecorder::class);
        $audit->shouldReceive('record')->times(3)->andThrow(new RuntimeException('audit unavailable'));
        $this->app->instance(AuditRecorder::class, $audit);
        $scopeKeys = CategoryHierarchyScope::query()->orderBy('scope_key')->pluck('scope_key')->all();
        foreach (['create', 'identity', 'reorder'] as $case) {
            try {
                match ($case) {
                    'create' => app(CreateCentralCategoryAction::class)->handle($this->actor, ['name' => 'New', 'slug' => 'new'], 0),
                    'identity' => app(UpdateCentralCategoryAction::class)->handle($this->actor, $a, ['name' => 'Changed', 'slug' => 'changed']),
                    'reorder' => app(ReorderCentralCategoriesAction::class)->handle($this->actor, null, [$b->id, $a->id], 0),
                };
                self::fail('Audit failure expected');
            } catch (RuntimeException) {
                self::assertSame(2, CentralCategory::query()->count());
                self::assertSame($a->name, $a->fresh()->name);
                self::assertSame([0, 1], [$a->fresh()->position, $b->fresh()->position]);
                self::assertSame(0, app(CategoryHierarchy::class)->revision(null));
                self::assertSame($scopeKeys, CategoryHierarchyScope::query()->orderBy('scope_key')->pluck('scope_key')->all());
                self::assertSame(0, AuditLogEntry::query()->count());
            }
        }
    }

    public function test_legacy_save_identity_failure_rolls_back_an_already_executed_reparent(): void
    {
        $a = $this->create('a');
        $b = $this->create('b', $a->id);
        try {
            app(SaveLegacyCentralCategoryAction::class)->handle($this->actor, $b,
                ['name' => 'Changed', 'slug' => 'a'], $a->id, null, 1, 1);
            self::fail('Duplicate slug accepted');
        } catch (ValidationException) {
            self::assertSame($a->id, $b->fresh()->parent_id);
            self::assertSame('b', $b->fresh()->name);
            self::assertSame(1, app(CategoryHierarchy::class)->revision(null));
            self::assertSame(1, app(CategoryHierarchy::class)->revision($a->id));
            self::assertSame(2, AuditLogEntry::query()->count());
        }
    }

    public function test_legacy_identity_edits_and_no_ops_do_not_lock_hierarchy_scopes(): void
    {
        $category = CentralCategory::factory()->create();
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        foreach (['Changed', 'Changed'] as $name) {
            app(SaveLegacyCentralCategoryAction::class)->handle($this->actor, $category,
                ['name' => $name, 'slug' => $category->slug], null, null, 0, 0);
            self::assertSame(1, AuditLogEntry::query()->count());
        }
        self::assertSame([], array_values(array_filter($queries, fn ($sql) => str_contains($sql, 'category_hierarchy_scopes'))));
        self::assertSame('Changed', $category->fresh()->name);
        self::assertSame(0, app(CategoryHierarchy::class)->revision(null));
    }

    public function test_tree_write_precedes_scope_and_sibling_reads_and_locks_are_deterministic(): void
    {
        $a = $this->create('a');
        $b = $this->create('b');
        $c = $this->create('c', $a->id);
        $queries = [];
        DB::listen(function (QueryExecuted $q) use (&$queries): void {
            $queries[] = [$q->sql, $q->bindings];
        });
        app(ReparentCentralCategoryAction::class)->handle($this->actor, $c, $b->id, 1, 0);
        self::assertStringContainsString('update', strtolower($queries[0][0]));
        self::assertStringContainsString('category_hierarchy_scopes', $queries[0][0]);
        $scopeReads = array_values(array_filter($queries, fn ($q) => str_contains($q[0], 'category_hierarchy_scopes') && str_starts_with(strtolower($q[0]), 'select') && isset($q[1][0]) && $q[1][0] !== 'root'));
        self::assertSame('parent:'.$a->id, $scopeReads[0][1][0]);
        self::assertSame('parent:'.$b->id, $scopeReads[count($scopeReads) - 1][1][0]);
        self::assertTrue(count(array_filter($queries, fn ($q) => str_contains($q[0], 'central_categories') && str_contains($q[0], 'order by') && str_contains($q[0], 'id'))) > 0);
    }
}
