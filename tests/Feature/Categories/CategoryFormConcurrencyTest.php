<?php

namespace Tests\Feature\Categories;

use App\Actions\CentralCatalog\CreateCentralCategoryAction;
use App\Actions\CentralCatalog\SaveCentralCategoryAction;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Services\Categories\CategoryHierarchy;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Tests\Support\BuildsCategorySaveData;
use Tests\Support\CategoryFormRace;
use Tests\TestCase;

/**
 * CA-018 composite saves under real two-process contention (SQLite shared file,
 * PostgreSQL and MariaDB via `composer test:database-boundaries`).
 *
 * Every competing submission is built from the same pre-race page state; the
 * second writer must reject on its stale expected state, never silently win.
 */
final class CategoryFormConcurrencyTest extends TestCase
{
    use BuildsCategorySaveData, CategoryFormRace, DatabaseTruncation;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actor = User::factory()->centralAdmin()->create();
    }

    protected function beforeTruncatingDatabase(): void
    {
        RefreshDatabaseState::$migrated = false;
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_two_creates_under_one_parent_from_the_same_form_state_cannot_share_a_revision(): void
    {
        $parent = CentralCategory::factory()->create(['position' => 0]);
        $revision = app(CategoryHierarchy::class)->revision($parent->id);
        $this->race('category_hierarchy_scopes',
            fn () => app(CreateCentralCategoryAction::class)->handle($this->actor, ['name' => 'Webcams', 'slug' => 'webcams', 'parent_id' => $parent->id], $revision),
            fn () => app(CreateCentralCategoryAction::class)->handle($this->actor, ['name' => 'Docks', 'slug' => 'docks', 'parent_id' => $parent->id], $revision),
            function (string $outcome) use ($parent): void {
                self::assertSame('validation:hierarchy_revision', $outcome);
                self::assertSame(['webcams'], CentralCategory::query()->where('parent_id', $parent->id)->pluck('slug')->all());
                self::assertSame(1, AuditLogEntry::query()->where('action', 'catalog.category.created')->count());
            });
    }

    public function test_two_display_order_saves_in_one_sibling_scope_serialize_and_the_second_is_stale(): void
    {
        [$a, $b, $c] = $this->siblings();
        $first = $this->categorySaveData($c, ['display_position' => 0]);
        $second = $this->categorySaveData($b, ['display_position' => 2]);
        $this->race('category_hierarchy_scopes',
            fn () => app(SaveCentralCategoryAction::class)->handle($this->actor, $c, $first),
            fn () => app(SaveCentralCategoryAction::class)->handle($this->actor, $b, $second),
            function (string $outcome) use ($a, $b, $c): void {
                self::assertSame('validation:hierarchy_revision', $outcome);
                self::assertSame([$c->id, $a->id, $b->id], CentralCategory::query()->whereNull('parent_id')->orderBy('position')->pluck('id')->all());
                self::assertSame(1, AuditLogEntry::query()->where('action', 'catalog.category.reordered')->count());
            });
    }

    public function test_identity_edit_from_an_old_form_rejects_after_a_concurrent_reparent(): void
    {
        [$a, $b] = $this->siblings();
        $move = $this->categorySaveData($a, ['parent_id' => $b->id]);
        $rename = $this->categorySaveData($a, ['name' => 'Renamed from a stale form']);
        $this->race('central_categories',
            fn () => app(SaveCentralCategoryAction::class)->handle($this->actor, $a, $move),
            fn () => app(SaveCentralCategoryAction::class)->handle($this->actor, $a, $rename),
            function (string $outcome) use ($a, $b): void {
                self::assertSame('validation:parent_id', $outcome);
                $current = CentralCategory::query()->findOrFail($a->id);
                self::assertSame([$b->id, 'a'], [$current->parent_id, $current->name]);
                self::assertSame(0, AuditLogEntry::query()->where('action', 'catalog.category.updated')->count());
            });
    }

    public function test_concurrent_identity_edits_cannot_lose_the_first_update(): void
    {
        [$a] = $this->siblings();
        $first = $this->categorySaveData($a, ['name' => 'First editor']);
        $second = $this->categorySaveData($a, ['name' => 'Second editor']);
        $this->race('central_categories',
            fn () => app(SaveCentralCategoryAction::class)->handle($this->actor, $a, $first),
            fn () => app(SaveCentralCategoryAction::class)->handle($this->actor, $a, $second),
            function (string $outcome) use ($a): void {
                self::assertSame('validation:category', $outcome);
                self::assertSame('First editor', CentralCategory::query()->findOrFail($a->id)->name);
                self::assertSame(1, AuditLogEntry::query()->where('action', 'catalog.category.updated')->count());
            });
    }

    public function test_opposite_composite_reparents_cannot_create_a_cycle(): void
    {
        [$a, $b] = $this->siblings();
        $aUnderB = $this->categorySaveData($a, ['parent_id' => $b->id]);
        $bUnderA = $this->categorySaveData($b, ['parent_id' => $a->id]);
        $this->race('category_hierarchy_scopes',
            fn () => app(SaveCentralCategoryAction::class)->handle($this->actor, $a, $aUnderB),
            fn () => app(SaveCentralCategoryAction::class)->handle($this->actor, $b, $bUnderA),
            function (string $outcome) use ($a, $b): void {
                self::assertSame('validation:hierarchy_revision', $outcome);
                self::assertSame($b->id, CentralCategory::query()->findOrFail($a->id)->parent_id);
                self::assertNull(CentralCategory::query()->findOrFail($b->id)->parent_id);
                self::assertSame(1, AuditLogEntry::query()->where('action', 'catalog.category.reparented')->count());
            });
    }

    /** @return list<CentralCategory> */
    private function siblings(): array
    {
        return array_map(fn (string $slug): CentralCategory => app(CreateCentralCategoryAction::class)->handle($this->actor,
            ['name' => $slug, 'slug' => $slug], app(CategoryHierarchy::class)->revision(null)), ['a', 'b', 'c']);
    }
}
