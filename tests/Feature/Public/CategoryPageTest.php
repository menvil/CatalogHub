<?php

namespace Tests\Feature\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_page_reads_the_localized_site_projection(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $category = CentralCategory::query()->where('slug', 'monitors')->firstOrFail();
        SiteCategoryProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_category_id' => $category->id,
            'slug' => 'best-monitors',
            'title' => 'Best Monitors',
            'status' => ProjectionStatus::Active,
            'payload_json' => ['category' => ['description' => 'Compare displays for work and play.', 'intro_text' => 'Independent monitor data.']],
            'seo_json' => ['description' => 'Monitor comparison guide.'],
        ]);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('http://tech-compare.test/en-US/categories/best-monitors')
            ->assertOk()
            ->assertSee('Best Monitors')
            ->assertSee('Compare displays for work and play.')
            ->assertSee('/en-US/categories/best-monitors/products', false);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertFalse(collect($queries)->contains(fn (array $query): bool => str_contains($query['query'], 'central_categories')));
    }

    public function test_missing_or_cross_locale_category_projection_returns_not_found(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);

        $this->get('http://tech-compare.test/en-US/categories/missing')->assertNotFound();
        $this->get('http://tech-compare.test/de-DE/categories/missing')->assertNotFound();
    }
}
