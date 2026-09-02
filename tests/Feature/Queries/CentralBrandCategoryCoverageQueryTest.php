<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Enums\CentralCategoryStatus;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Queries\CentralCatalog\CentralBrandCategoryCoverageQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CentralBrandCategoryCoverageQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_direct_current_products_only_and_preserves_category_status(): void
    {
        $brand = CentralBrand::factory()->create();
        $otherBrand = CentralBrand::factory()->create();
        $smartphones = $this->category('Smartphones', CentralCategoryStatus::Active);
        $televisions = $this->category('Televisions', CentralCategoryStatus::Archived);
        $laptops = $this->category('Laptops', CentralCategoryStatus::Draft);

        CentralProduct::factory()->count(2)->create(['central_brand_id' => $brand->id, 'central_category_id' => $smartphones->id, 'status' => CentralProductStatus::Active]);
        CentralProduct::factory()->create(['central_brand_id' => $brand->id, 'central_category_id' => $televisions->id, 'status' => CentralProductStatus::Draft]);
        CentralProduct::factory()->create(['central_brand_id' => $brand->id, 'central_category_id' => $laptops->id, 'status' => CentralProductStatus::Archived]);
        CentralProduct::factory()->count(3)->create(['central_brand_id' => $otherBrand->id, 'central_category_id' => $smartphones->id, 'status' => CentralProductStatus::Active]);
        CentralProduct::factory()->create(['central_brand_id' => $brand->id, 'central_category_id' => null, 'status' => CentralProductStatus::Active]);

        $coverage = app(CentralBrandCategoryCoverageQuery::class)->forBrand($brand);

        self::assertSame(['Smartphones', 'Televisions'], $coverage->pluck('name')->all());
        self::assertSame([2, 1], $coverage->pluck('productsCount')->all());
        self::assertSame([CentralCategoryStatus::Active, CentralCategoryStatus::Archived], $coverage->pluck('status')->all());
    }

    public function test_product_move_and_last_product_archive_change_coverage_without_brand_persistence(): void
    {
        $brand = CentralBrand::factory()->create();
        $smartphones = $this->category('Smartphones');
        $tablets = $this->category('Tablets');
        $product = CentralProduct::factory()->create([
            'central_brand_id' => $brand->id,
            'central_category_id' => $smartphones->id,
            'status' => CentralProductStatus::Active,
        ]);
        $query = app(CentralBrandCategoryCoverageQuery::class);

        self::assertSame(['Smartphones'], $query->forBrand($brand)->pluck('name')->all());
        $product->update(['central_category_id' => $tablets->id]);
        self::assertSame(['Tablets'], $query->forBrand($brand)->pluck('name')->all());
        $product->update(['status' => CentralProductStatus::Archived]);
        self::assertTrue($query->forBrand($brand)->isEmpty());
    }

    public function test_large_fixture_uses_one_grouped_query_and_deterministic_count_name_sorting(): void
    {
        $brand = CentralBrand::factory()->create();
        foreach ([['Tablets', 2], ['Phones', 4], ['Accessories', 2]] as [$name, $count]) {
            $category = $this->category($name);
            CentralProduct::factory()->count($count)->create([
                'central_brand_id' => $brand->id,
                'central_category_id' => $category->id,
                'status' => CentralProductStatus::Active,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $coverage = app(CentralBrandCategoryCoverageQuery::class)->forBrand($brand);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        self::assertCount(1, $queries);
        self::assertSame(['Phones', 'Accessories', 'Tablets'], $coverage->pluck('name')->all());
        self::assertStringContainsString('group by', strtolower($queries[0]['query']));
    }

    public function test_page_counts_are_distinct_grouped_and_exclude_archived_or_uncategorized_products(): void
    {
        $first = CentralBrand::factory()->create();
        $second = CentralBrand::factory()->create();
        $categoryA = $this->category('Cameras');
        $categoryB = $this->category('Lenses');
        CentralProduct::factory()->count(2)->create([
            'central_brand_id' => $first->id,
            'central_category_id' => $categoryA->id,
            'status' => CentralProductStatus::Active,
        ]);
        CentralProduct::factory()->create([
            'central_brand_id' => $first->id,
            'central_category_id' => $categoryB->id,
            'status' => CentralProductStatus::Draft,
        ]);
        CentralProduct::factory()->create([
            'central_brand_id' => $first->id,
            'central_category_id' => $categoryB->id,
            'status' => CentralProductStatus::Archived,
        ]);
        CentralProduct::factory()->create([
            'central_brand_id' => $second->id,
            'central_category_id' => null,
            'status' => CentralProductStatus::Active,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $counts = app(CentralBrandCategoryCoverageQuery::class)->countsForBrands(collect([$first, $second]));
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        self::assertCount(1, $queries);
        self::assertSame(2, $counts->get($first->id));
        self::assertFalse($counts->has($second->id));
        self::assertStringContainsString('count(distinct', strtolower($queries[0]['query']));
    }

    private function category(string $name, CentralCategoryStatus $status = CentralCategoryStatus::Active): CentralCategory
    {
        return CentralCategory::factory()->create([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.bin2hex(random_bytes(3)),
            'status' => $status,
        ]);
    }
}
