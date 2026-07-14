<?php

namespace Tests\Feature\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmptyCategoryStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_with_zero_product_projections_renders_an_actionable_empty_state(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $category = CentralCategory::query()->where('slug', 'mice')->firstOrFail();
        SiteCategoryProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_category_id' => $category->id,
            'slug' => 'mice',
            'title' => 'Mice',
            'status' => ProjectionStatus::Active,
            'payload_json' => [],
        ]);

        $this->get('http://tech-compare.test/en-US/categories/mice/products')
            ->assertOk()
            ->assertSee('No products here yet')
            ->assertSee('No projected products are available in this category yet.')
            ->assertSee('Back to Mice')
            ->assertSee('https://tech-compare.test/en-US/categories/mice', false)
            ->assertDontSee('data-product-card', false);
    }
}
