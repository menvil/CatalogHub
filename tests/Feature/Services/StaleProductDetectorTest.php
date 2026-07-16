<?php

namespace Tests\Feature\Services;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Services\Sync\StaleProductDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaleProductDetectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_detects_a_site_product_when_the_central_version_is_newer(): void
    {
        $product = CentralProduct::factory()->create(['version' => 5]);
        $siteProduct = SiteProduct::factory()
            ->for($product, 'centralProduct')
            ->create(['published_version' => 3]);

        $this->assertTrue(app(StaleProductDetector::class)->isStale($siteProduct));
    }

    public function test_equal_versions_are_fresh(): void
    {
        $product = CentralProduct::factory()->create(['version' => 5]);
        $siteProduct = SiteProduct::factory()
            ->for($product, 'centralProduct')
            ->create(['published_version' => 5, 'sync_status' => 'completed']);

        $this->assertFalse(app(StaleProductDetector::class)->isStale($siteProduct));
    }

    public function test_failed_sync_status_is_stale_even_at_the_current_version(): void
    {
        $product = CentralProduct::factory()->create(['version' => 2]);
        $siteProduct = SiteProduct::factory()
            ->for($product, 'centralProduct')
            ->create(['published_version' => 2, 'sync_status' => 'failed']);

        $this->assertTrue(app(StaleProductDetector::class)->isStale($siteProduct));
    }

    public function test_stale_query_returns_only_stale_products_for_the_requested_site(): void
    {
        $site = Site::factory()->create();
        $otherSite = Site::factory()->create();
        $staleProduct = SiteProduct::factory()
            ->for($site)
            ->for(CentralProduct::factory()->state(['version' => 4]), 'centralProduct')
            ->create(['published_version' => 2]);
        SiteProduct::factory()
            ->for($site)
            ->for(CentralProduct::factory()->state(['version' => 4]), 'centralProduct')
            ->create(['published_version' => 4, 'sync_status' => 'completed']);
        SiteProduct::factory()
            ->for($otherSite)
            ->for(CentralProduct::factory()->state(['version' => 4]), 'centralProduct')
            ->create(['published_version' => 1]);

        $result = app(StaleProductDetector::class)->staleForSite($site)->get();

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($staleProduct));
    }
}
