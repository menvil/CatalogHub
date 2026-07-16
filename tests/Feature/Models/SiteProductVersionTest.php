<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\SiteProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiteProductVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_the_applied_central_version_and_sync_metadata(): void
    {
        $syncedAt = now()->startOfSecond();
        $siteProduct = SiteProduct::factory()->create([
            'published_version' => 3,
            'last_synced_at' => $syncedAt,
            'sync_status' => 'completed',
        ]);

        $this->assertSame(3, $siteProduct->published_version);
        $this->assertTrue($siteProduct->last_synced_at->equalTo($syncedAt));
        $this->assertSame('completed', $siteProduct->sync_status);
        $this->assertTrue(Schema::hasColumns('site_products', [
            'published_version',
            'last_synced_at',
            'sync_status',
        ]));
    }

    public function test_published_version_defaults_to_zero_and_product_aliases_are_available(): void
    {
        $product = CentralProduct::factory()->create();
        $siteProduct = SiteProduct::factory()->for($product, 'centralProduct')->create();

        $this->assertSame(0, $siteProduct->published_version);
        $this->assertTrue($siteProduct->centralProduct->is($product));
        $this->assertTrue($siteProduct->product->is($product));
    }
}
