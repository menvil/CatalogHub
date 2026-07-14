<?php

namespace Tests\Feature\Demo;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\Demo\PublicDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_consolidated_seed_creates_projection_data_for_end_to_end_public_routes(): void
    {
        $this->seed(PublicDemoSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();

        $this->assertGreaterThanOrEqual(3, SiteCategoryProjection::query()->where('site_id', $site->id)->where('status', ProjectionStatus::Active)->count());
        $this->assertGreaterThanOrEqual(12, SiteProductProjection::query()->where('site_id', $site->id)->where('status', ProjectionStatus::Active)->count());
        $this->assertGreaterThanOrEqual(15, SiteSearchDocument::query()->where('site_id', $site->id)->where('status', ProjectionStatus::Active)->count());
        $this->assertTrue(SiteProductProjection::query()->where('site_id', $site->id)->get()->contains(fn (SiteProductProjection $projection): bool => data_get($projection->media_json, 'main.is_placeholder') === false));
        $this->assertTrue(SiteProductProjection::query()->where('site_id', $site->id)->get()->contains(fn (SiteProductProjection $projection): bool => data_get($projection->media_json, 'main.is_placeholder') === true));

        $this->get('http://tech-compare.test/en-US')->assertOk()->assertSee('Aurora 27 Pro');
        $this->get('http://tech-compare.test/en-US/categories/monitors')->assertOk()->assertSee('Monitors');
        $this->get('http://tech-compare.test/en-US/categories/monitors/products')->assertOk()->assertSee('Aurora 27 Pro');
        $this->get('http://tech-compare.test/en-US/products/aurora-27-pro')->assertOk()->assertSee('Refresh rate');
        $this->get('http://tech-compare.test/en-US/compare?products=aurora-27-pro,horizon-32-studio')
            ->assertOk()
            ->assertSee('165 Hz')
            ->assertSee('144 Hz');

        $productCount = SiteProductProjection::query()->count();
        $categoryCount = SiteCategoryProjection::query()->count();
        $this->seed(PublicDemoSeeder::class);
        $this->assertSame($productCount, SiteProductProjection::query()->count());
        $this->assertSame($categoryCount, SiteCategoryProjection::query()->count());
    }

    public function test_database_seeder_can_be_run_repeatedly(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'test@example.com')->count());
        $this->assertSame(20, SiteProductProjection::query()->count());
    }
}
