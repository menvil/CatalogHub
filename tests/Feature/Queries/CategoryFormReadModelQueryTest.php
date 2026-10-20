<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Models\CentralCatalog\CategoryHierarchyScope;
use App\Models\CentralCatalog\CentralCategory;
use App\Queries\CentralCatalog\CategoryDetailReadModelQuery;
use App\Queries\CentralCatalog\CategoryFormReadModelQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CategoryCreateEditFixture as Fixture;
use Tests\TestCase;

final class CategoryFormReadModelQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_lists_every_category_in_tree_order_with_paths_and_root_revision(): void
    {
        Fixture::create();
        $form = app(CategoryFormReadModelQuery::class)->forCreate();
        self::assertNull($form->category);
        self::assertNull($form->summary);
        self::assertSame([], $form->siblings);
        self::assertSame([
            'Electronics', 'Electronics / Laptops', 'Electronics / Displays', 'Electronics / Displays / Televisions',
            'Electronics / Displays / Monitors', 'Electronics / Displays / Monitors / Gaming Monitors · Draft',
            'Electronics / Displays / Projectors · Draft', 'Electronics / Phones · Draft',
            'Home & Garden', 'Home & Garden / Garden Tools', 'Legacy Peripherals · Archived', 'Smart Home · Draft',
        ], array_column($form->parentOptions, 'label'));
        self::assertStringContainsString('monitors #'.Fixture::MONITORS, $form->parentOptions[4]['search']);
        self::assertSame(1, $form->scopeRevisionFor(null));
        self::assertSame(1, $form->scopeRevisionFor(Fixture::DISPLAYS));
        self::assertSame(0, $form->scopeRevisionFor(Fixture::MONITORS));
    }

    public function test_edit_excludes_self_and_descendants_and_exposes_real_sibling_order_and_summary(): void
    {
        $monitors = Fixture::create();
        $form = app(CategoryFormReadModelQuery::class)->forEdit($monitors);
        $values = array_column($form->parentOptions, 'value');
        self::assertNotContains((string) Fixture::MONITORS, $values);
        self::assertNotContains((string) Fixture::GAMING_MONITORS, $values);
        self::assertContains((string) Fixture::LEGACY, $values);
        self::assertSame([['id' => Fixture::ELECTRONICS, 'name' => 'Electronics'], ['id' => Fixture::DISPLAYS, 'name' => 'Displays']], $form->ancestors);
        self::assertSame([Fixture::TELEVISIONS, Fixture::MONITORS, Fixture::PROJECTORS], array_column($form->siblings, 'id'));
        self::assertSame([1, true, Fixture::DISPLAYS], [$form->position(), $form->siblingOrderContiguous, $form->parentId]);
        self::assertSame(1, $form->scopeRevisionFor($form->parentId));
        $detail = app(CategoryDetailReadModelQuery::class)->forCategory($monitors->refresh());
        self::assertSame([$detail->schema->sections, $detail->schema->attributes, $detail->products, $detail->selectedSites],
            [$form->summary->sections, $form->summary->assignments, $form->summary->products, $form->summary->selectedSites]);
        self::assertTrue($form->summary->withSchema());
        self::assertNotNull($form->translationLocaleId);
    }

    public function test_reads_never_initialize_scopes_or_normalize_legacy_positions(): void
    {
        $parent = CentralCategory::factory()->create(['position' => 0]);
        $first = CentralCategory::factory()->create(['parent_id' => $parent->id, 'position' => 4]);
        $second = CentralCategory::factory()->create(['parent_id' => $parent->id, 'position' => 4]);
        $scopes = CategoryHierarchyScope::query()->orderBy('scope_key')->get()->toJson();
        $categories = DB::table('central_categories')->orderBy('id')->get()->toJson();
        $form = app(CategoryFormReadModelQuery::class)->forEdit($second);
        self::assertFalse($form->siblingOrderContiguous);
        self::assertSame([$first->id, $second->id], array_column($form->siblings, 'id'));
        self::assertSame(0, $form->scopeRevisionFor($parent->id));
        app(CategoryFormReadModelQuery::class)->forCreate();
        self::assertSame($scopes, CategoryHierarchyScope::query()->orderBy('scope_key')->get()->toJson());
        self::assertSame($categories, DB::table('central_categories')->orderBy('id')->get()->toJson());
    }

    public function test_unreachable_legacy_cycles_stay_identifiable_and_hierarchy_is_reported_unavailable(): void
    {
        $a = CentralCategory::factory()->create(['name' => 'Cycle A']);
        $b = CentralCategory::factory()->create(['name' => 'Cycle B', 'parent_id' => $a->id]);
        DB::table('central_categories')->where('id', $a->id)->update(['parent_id' => $b->id]);
        $other = CentralCategory::factory()->create(['name' => 'Unrelated']);
        $form = app(CategoryFormReadModelQuery::class)->forEdit($other);
        self::assertContains('Cycle A (hierarchy unavailable)', array_column($form->parentOptions, 'label'));
        self::assertFalse(app(CategoryFormReadModelQuery::class)->forEdit($a->refresh())->hierarchyAvailable);
    }

    public function test_identical_parent_paths_are_disambiguated_by_stable_id(): void
    {
        Fixture::create();
        $duplicate = CentralCategory::factory()->create(['name' => 'Electronics', 'slug' => 'electronics-registry', 'position' => 4, 'status' => 'active']);
        $labels = array_column(app(CategoryFormReadModelQuery::class)->forCreate()->parentOptions, 'label');
        self::assertContains('Electronics · #'.Fixture::ELECTRONICS, $labels);
        self::assertContains('Electronics · #'.$duplicate->id, $labels);
        self::assertContains('Electronics / Displays', $labels);
    }

    public function test_query_count_is_bounded_independently_of_tree_size(): void
    {
        $monitors = Fixture::create();
        $count = function () use ($monitors): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            app(CategoryFormReadModelQuery::class)->forEdit($monitors->refresh());
            $queries = count(DB::getQueryLog()) - 1;
            DB::disableQueryLog();

            return $queries;
        };
        $small = $count();
        CentralCategory::factory()->count(40)->sequence(fn ($sequence) => ['parent_id' => Fixture::DISPLAYS, 'position' => $sequence->index + 3])->create();
        self::assertSame($small, $count());
        self::assertLessThanOrEqual(8, $small);
    }
}
