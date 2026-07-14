<?php

namespace Tests\Feature\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingNoResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_filtered_no_results_state_with_clear_action(): void
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

        $this->get('http://tech-compare.test/en-US/categories/monitors/products?brand=nonexistent')
            ->assertOk()
            ->assertSee('data-filtered-no-results', false)
            ->assertSee('No products match your filters')
            ->assertSee('Clear all filters')
            ->assertSee('https://tech-compare.test/en-US/categories/monitors/products', false)
            ->assertDontSee('No products here yet');
    }
}
