<?php

namespace Tests\Feature\Actions;

use App\Actions\Sync\RebuildSiteProductProjectionAction;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RebuildSiteProductProjectionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_can_rebuild_and_publish_the_current_product_version(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en-US']);
        $product = CentralProduct::factory()->create(['version' => 4]);
        $siteProduct = SiteProduct::factory()
            ->for($site)
            ->for($product, 'centralProduct')
            ->create(['published_version' => 2]);

        $rebuilt = app(RebuildSiteProductProjectionAction::class)->handle(
            User::factory()->centralAdmin()->create(),
            $siteProduct,
        );

        $this->assertSame(4, $rebuilt->published_version);
        $this->assertSame('completed', $rebuilt->sync_status);
        $this->assertNotNull($rebuilt->last_synced_at);
        $this->assertDatabaseHas('site_product_projections', [
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'locale' => 'en-US',
        ]);
        $this->assertDatabaseHas('sync_logs', [
            'operation' => 'rebuild_product_projection',
            'status' => 'completed',
            'site_id' => $site->id,
            'central_product_id' => $product->id,
        ]);
    }

    public function test_site_admin_cannot_rebuild_from_the_central_screen(): void
    {
        $site = Site::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(RebuildSiteProductProjectionAction::class)->handle(
            User::factory()->siteAdmin($site)->create(),
            SiteProduct::factory()->for($site)->create(),
        );
    }
}
