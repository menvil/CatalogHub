<?php

namespace Tests\Feature\CentralAdmin;

use App\Filament\Resources\StaleProductResource;
use App\Filament\Resources\StaleProductResource\Pages\ListStaleProducts;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaleProductsScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_screen_shows_stale_products_and_excludes_fresh_products(): void
    {
        $site = Site::factory()->create();
        $stale = SiteProduct::factory()
            ->for($site)
            ->for(CentralProduct::factory()->state(['name' => 'Stale monitor', 'version' => 5]), 'centralProduct')
            ->create(['published_version' => 2]);
        $fresh = SiteProduct::factory()
            ->for($site)
            ->for(CentralProduct::factory()->state(['name' => 'Fresh monitor', 'version' => 5]), 'centralProduct')
            ->create(['published_version' => 5, 'sync_status' => 'completed']);
        $admin = User::factory()->centralAdmin()->create();

        $this->actingAs($admin)
            ->get(StaleProductResource::getUrl())
            ->assertOk()
            ->assertSee('Stale Products')
            ->assertSee('Stale monitor')
            ->assertDontSee('Fresh monitor');

        Livewire::actingAs($admin)
            ->test(ListStaleProducts::class)
            ->assertCanSeeTableRecords([$stale])
            ->assertCanNotSeeTableRecords([$fresh])
            ->assertTableActionExists('rebuild', record: $stale);
    }

    public function test_stale_screen_filters_by_site_and_version_gap(): void
    {
        $site = Site::factory()->create();
        $largeGap = SiteProduct::factory()
            ->for($site)
            ->for(CentralProduct::factory()->state(['version' => 7]), 'centralProduct')
            ->create(['published_version' => 2]);
        $smallGap = SiteProduct::factory()
            ->for($site)
            ->for(CentralProduct::factory()->state(['version' => 3]), 'centralProduct')
            ->create(['published_version' => 2]);
        $otherSite = SiteProduct::factory()
            ->for(Site::factory())
            ->for(CentralProduct::factory()->state(['version' => 9]), 'centralProduct')
            ->create(['published_version' => 1]);

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(ListStaleProducts::class)
            ->filterTable('site_id', $site->id)
            ->filterTable('version_gap', 5)
            ->assertCanSeeTableRecords([$largeGap])
            ->assertCanNotSeeTableRecords([$smallGap, $otherSite]);
    }

    public function test_site_admin_cannot_open_stale_products_screen(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(StaleProductResource::getUrl())
            ->assertForbidden();
    }
}
