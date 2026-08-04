<?php

namespace Tests\Feature\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\DatabaseQueryCounter;
use Tests\TestCase;

class ProductListingPriceBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_cards_show_projected_price_availability_and_freshness(): void
    {
        [$site, $category] = $this->listingScenario();
        $this->productDocument($site, $category, 'priced-display', 'Priced Display', [
            'min_price' => '249.99',
            'max_price' => '319.99',
            'offers_count' => 6,
            'in_stock' => true,
            'last_price_update_at' => now()->subHours(2),
        ]);
        $this->productDocument($site, $category, 'uncovered-display', 'Uncovered Display');

        $this->get('http://tech-compare.test/en-US/categories/monitors/products')
            ->assertOk()
            ->assertSee('From $249.99')
            ->assertSee('6 offers')
            ->assertSee('In stock')
            ->assertSee('Updated 2 hours ago')
            ->assertSee('No current offers');
    }

    public function test_search_list_cards_use_the_same_projected_price_block(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        SiteSearchDocument::factory()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'title' => 'Search Price Display',
            'slug' => 'search-price-display',
            'search_text' => 'Search Price Display',
            'min_price' => '199.00',
            'offers_count' => 2,
            'in_stock' => false,
            'last_price_update_at' => now()->subHours(12),
        ]);

        $this->get('http://tech-compare.test/en-US/search?q=Search+Price')
            ->assertOk()
            ->assertSee('data-variant="list"', false)
            ->assertSee('From $199.00')
            ->assertSee('2 offers')
            ->assertSee('Currently unavailable')
            ->assertSee('Price may be outdated');
    }

    public function test_listing_price_blocks_do_not_query_offers_per_product(): void
    {
        [$site, $category] = $this->listingScenario();

        foreach (range(1, 5) as $index) {
            $this->productDocument($site, $category, "display-{$index}", "Display {$index}", [
                'min_price' => (string) (100 + $index),
                'offers_count' => $index,
                'in_stock' => true,
                'last_price_update_at' => now(),
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('http://tech-compare.test/en-US/categories/monitors/products')->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $offerQueries = collect($queries)
            ->filter(fn (array $query): bool => str_contains($query['query'], 'market_offers'));

        $this->assertLessThanOrEqual(1, $offerQueries->count());
    }

    public function test_listing_query_count_does_not_grow_with_more_cards(): void
    {
        [$site, $category] = $this->listingScenario();
        $url = 'http://tech-compare.test/en-US/categories/monitors/products';
        $this->productDocument($site, $category, 'budget-display-1', 'Budget Display 1');
        $this->get($url)->assertOk();

        $baseline = DatabaseQueryCounter::measure(fn () => $this->get($url));

        foreach (range(2, 20) as $index) {
            $this->productDocument(
                $site,
                $category,
                "budget-display-{$index}",
                "Budget Display {$index}",
            );
        }

        $expanded = DatabaseQueryCounter::measure(fn () => $this->get($url));

        $baseline['result']->assertOk();
        $expanded['result']->assertOk();
        $this->assertLessThanOrEqual($baseline['count'], $expanded['count']);
        $this->assertLessThanOrEqual(20, $expanded['count']);
    }

    /** @return array{Site, CentralCategory} */
    private function listingScenario(): array
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $category = CentralCategory::query()->where('slug', 'monitors')->firstOrFail();
        SiteCategoryProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_category_id' => $category->id,
            'slug' => 'monitors',
            'title' => 'Monitors',
            'status' => ProjectionStatus::Active,
            'payload_json' => [],
        ]);

        return [$site, $category];
    }

    /** @param array<string, mixed> $priceFields */
    private function productDocument(
        Site $site,
        CentralCategory $category,
        string $slug,
        string $title,
        array $priceFields = [],
    ): void {
        $product = CentralProduct::factory()->create(['central_category_id' => $category->id]);
        SiteProductProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_product_id' => $product->id,
            'slug' => $slug,
            'title' => $title,
            'status' => ProjectionStatus::Active,
            'payload_json' => [],
            'media_json' => [],
            'search_summary_json' => [],
            'built_at' => now(),
        ]);
        SiteSearchDocument::factory()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'document_id' => $product->id,
            'title' => $title,
            'slug' => $slug,
            'filter_values_json' => ['category_id' => $category->id],
            ...$priceFields,
        ]);
    }
}
