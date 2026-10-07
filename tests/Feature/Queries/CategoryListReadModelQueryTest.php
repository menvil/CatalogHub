<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Data\CentralCatalog\CategoryListFiltersData;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use App\Models\Locale;
use App\Models\Site;
use App\Models\Translations\CategoryTranslation;
use App\Queries\CentralCatalog\CategoryListReadModelQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CategoryListFixture;
use Tests\TestCase;

final class CategoryListReadModelQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_and_rows_use_direct_current_state_not_readiness_or_publication(): void
    {
        CategoryListFixture::create();
        $list = app(CategoryListReadModelQuery::class)->paginate(new CategoryListFiltersData(perPage: 50));
        self::assertSame(25, $list->summary->total);
        self::assertSame(15, $list->summary->active);
        self::assertSame(60.0, $list->summary->activePercentage());
        self::assertSame(6, $list->summary->withSchema);
        self::assertSame(2, $list->summary->missingTranslations);
        self::assertSame(10, $list->summary->needsReview);
        $rows = $list->categories->getCollection()->keyBy(fn ($row) => $row->category->id);
        $displays = $rows->get(194004);
        self::assertSame([3, 2, 1, 3], [$displays->products, $displays->attributes, $displays->facets, $displays->sites]);
        self::assertSame([4, 2, 1, 3], [$rows->get(194005)->products, $rows->get(194005)->attributes, $rows->get(194005)->facets, $rows->get(194005)->sites]);
        self::assertSame([0, 1, 2, 3], array_map(fn ($id) => $rows->get($id)->depth, [194003, 194004, 194005, 194006]));
        self::assertSame('Gaming Monitors', $rows->get(194006)->parentName);
        self::assertSame([2, 0, 2, 0], [$rows->get(194002)->localeTotal, $rows->get(194002)->covered, $rows->get(194002)->missing, $rows->get(194002)->outdated]);
        self::assertSame([1, 0, 1, 50], [$rows->get(194006)->covered, $rows->get(194006)->missing, $rows->get(194006)->outdated, $rows->get(194006)->coveragePercentage()]);
        self::assertSame([0, 0, 0, 0], [$rows->get(194008)->products, $rows->get(194008)->attributes, $rows->get(194008)->facets, $rows->get(194008)->sites]);
        self::assertSame([0 => 'Root', 1 => 'Level 1', 2 => 'Level 2', 3 => 'Level 3'], $list->levelOptions);
        // Losing assignment presence removes With Schema, irrespective of Approved state.
        FacetDefinition::query()->where('category_id', 194001)->delete();
        CategoryAttributeAssignment::query()->where('central_category_id', 194001)->delete();
        self::assertSame(5, app(CategoryListReadModelQuery::class)->paginate(new CategoryListFiltersData)->summary->withSchema);
    }

    public function test_server_filters_and_locale_context_are_composable(): void
    {
        CategoryListFixture::create();
        $query = app(CategoryListReadModelQuery::class);
        foreach ([
            [new CategoryListFiltersData(search: 'Gaming'), [194005]],
            [new CategoryListFiltersData(search: 'registry-coffee'), [194002]],
            [new CategoryListFiltersData(status: 'archived'), [194015]],
            [new CategoryListFiltersData(schemaStatus: 'archived'), [194015]],
            [new CategoryListFiltersData(level: 3), [194006]],
            [new CategoryListFiltersData(level: 99), []],
            [new CategoryListFiltersData(translation: 'missing'), [194002, 194005]],
            [new CategoryListFiltersData(translation: 'outdated'), [194006]],
            [new CategoryListFiltersData(search: 'Gaming', status: 'active', schemaStatus: 'draft', level: 2, siteId: 194010), [194005]],
        ] as [$filters, $ids]) {
            $actual = $query->paginate($filters)->categories->getCollection()->map(fn ($row) => $row->category->id)->all();
            sort($actual);
            sort($ids);
            self::assertSame($ids, $actual);
        }
        $locale = Locale::query()->where('code', 'de-DE')->sole();
        $list = $query->paginate(new CategoryListFiltersData(search: 'Gaming', localeId: $locale->id));
        self::assertSame(TranslationStatus::Missing, $list->categories->first()->localeStatus);
        $other = Locale::query()->where('code', 'en-US')->sole();
        self::assertSame(TranslationStatus::HumanReviewed, $query->paginate(new CategoryListFiltersData(search: 'Gaming', localeId: $other->id))->categories->first()->localeStatus);
        self::assertSame([194002], $query->paginate(new CategoryListFiltersData(localeId: $other->id, translation: 'missing'))->categories->getCollection()->map(fn ($row) => $row->category->id)->all());
    }

    public function test_zero_locale_denominator_is_neutral_and_inactive_locales_are_ignored(): void
    {
        $category = CentralCategory::factory()->create();
        $inactive = Locale::factory()->create(['is_active' => false]);
        CategoryTranslation::factory()->create(['category_id' => $category->id, 'locale_id' => $inactive->id, 'status' => 'missing']);
        $list = app(CategoryListReadModelQuery::class)->paginate(new CategoryListFiltersData);
        self::assertSame(0, $list->summary->missingTranslations);
        self::assertSame(0, $list->categories->first()->localeTotal);
        self::assertNull($list->categories->first()->coveragePercentage());
    }

    public function test_site_selection_counts_exclude_archived_and_deleted_but_retain_disabled_selections(): void
    {
        CategoryListFixture::create();
        Site::query()->whereKey(194011)->delete();
        $row = app(CategoryListReadModelQuery::class)->paginate(new CategoryListFiltersData(search: 'registry-cameras'))->categories->first();
        self::assertSame(2, $row->sites);
    }

    public function test_all_sorts_and_pagination_use_id_as_a_stable_tie_breaker(): void
    {
        $categories = CentralCategory::factory()->count(25)->create(['name' => 'Same', 'status' => 'draft', 'schema_status' => 'draft', 'updated_at' => '2026-10-07 09:00:00']);
        $query = app(CategoryListReadModelQuery::class);
        foreach (['name', 'products', 'attributes', 'facets', 'sites', 'status', 'schema_status', 'updated_at'] as $sort) {
            foreach (['asc', 'desc'] as $direction) {
                $filters = new CategoryListFiltersData(sort: $sort, direction: $direction);
                $one = $query->paginate($filters, 1)->categories;
                $two = $query->paginate($filters, 2)->categories;
                $ids = [...$one->getCollection()->map(fn ($row) => $row->category->id)->all(), ...$two->getCollection()->map(fn ($row) => $row->category->id)->all()];
                self::assertSame($categories->modelKeys(), $ids);
                self::assertSame(25, $one->total());
                self::assertCount(5, $two);
            }
        }
        foreach ([20, 50, 100] as $perPage) {
            self::assertSame($perPage, $query->paginate(new CategoryListFiltersData(perPage: $perPage))->categories->perPage());
        }
    }

    public function test_aggregate_sorts_use_actual_counts_and_updated_and_lifecycle_values(): void
    {
        CategoryListFixture::create();
        $query = app(CategoryListReadModelQuery::class);
        self::assertSame('Keyboards', $query->paginate(new CategoryListFiltersData(sort: 'products', direction: 'desc'))->categories->first()->category->name);
        foreach (['attributes', 'facets', 'sites', 'updated_at'] as $sort) {
            self::assertSame('Cameras', $query->paginate(new CategoryListFiltersData(sort: $sort, direction: 'desc'))->categories->first()->category->name);
        }
        self::assertSame('Coffee Machines', $query->paginate(new CategoryListFiltersData(sort: 'status', direction: 'desc'))->categories->first()->category->name);
        self::assertSame('Electronics', $query->paginate(new CategoryListFiltersData(sort: 'schema_status', direction: 'desc'))->categories->first()->category->name);
    }

    public function test_page_query_count_is_bounded_from_one_to_twenty_five_categories(): void
    {
        $category = CentralCategory::factory()->create();
        $locale = Locale::factory()->create();
        CategoryTranslation::factory()->create(['category_id' => $category->id, 'locale_id' => $locale->id]);
        $query = app(CategoryListReadModelQuery::class);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $query->paginate(new CategoryListFiltersData(perPage: 50));
        $one = count(DB::getQueryLog());
        DB::disableQueryLog();
        CentralCategory::factory()->count(24)->create();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $query->paginate(new CategoryListFiltersData(perPage: 50));
        $many = count(DB::getQueryLog());
        DB::disableQueryLog();
        self::assertSame($one, $many);
        self::assertLessThanOrEqual(8, $many);
    }

    public function test_corrupt_cycles_are_bounded_and_never_repaired_by_a_read(): void
    {
        $a = CentralCategory::factory()->create();
        $b = CentralCategory::factory()->create(['parent_id' => $a->id]);
        $a->update(['parent_id' => $b->id]);
        $list = app(CategoryListReadModelQuery::class)->paginate(new CategoryListFiltersData);
        self::assertSame([null, null], $list->categories->getCollection()->map(fn ($row) => $row->depth)->all());
        self::assertSame($b->id, $a->fresh()->parent_id);
    }
}
