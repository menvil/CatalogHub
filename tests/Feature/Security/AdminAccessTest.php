<?php

namespace Tests\Feature\Security;

use App\Filament\Pages\TranslationDashboard;
use App\Filament\Resources\CatalogSnapshotResource;
use App\Filament\Resources\CentralBrandResource;
use App\Filament\Resources\CentralCategoryResource;
use App\Filament\Resources\CentralProductResource;
use App\Filament\Resources\MarketResource;
use App\Filament\Resources\SiteResource;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_can_open_critical_central_areas(): void
    {
        $this->actingAs(User::factory()->centralAdmin()->create());

        foreach ($this->centralUrls() as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_catalog_editor_has_catalog_and_media_access_but_not_market_or_backup_access(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([
            CentralProductResource::getUrl('index'),
            CentralCategoryResource::getUrl('index'),
            CentralBrandResource::getUrl('index'),
            route('central.media.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(MarketResource::getUrl('index'))->assertForbidden();
        $this->get(CatalogSnapshotResource::getUrl('index'))->assertForbidden();
    }

    public function test_translator_is_limited_to_translation_area(): void
    {
        $translator = User::factory()->create(['role' => 'translator']);

        $this->actingAs($translator)
            ->get(TranslationDashboard::getUrl())
            ->assertOk();
        $this->get(CentralProductResource::getUrl('index'))->assertForbidden();
        $this->get(route('central.media.index'))->assertForbidden();
    }

    public function test_site_admin_is_scoped_to_their_own_site_and_blocked_from_central_areas(): void
    {
        $ownSite = Site::factory()->create(['name' => 'Own Site']);
        $otherSite = Site::factory()->create(['name' => 'Other Site']);
        $this->actingAs(User::factory()->siteAdmin($ownSite)->create());

        $this->get(SiteResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Own Site')
            ->assertDontSee('Other Site');
        $this->get(SiteResource::getUrl('edit', ['record' => $otherSite]))->assertNotFound();

        foreach ($this->centralUrls() as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_guest_is_redirected_from_admin_and_custom_central_routes(): void
    {
        $this->get(CentralProductResource::getUrl('index'))->assertRedirect('/admin/login');
        $this->get(route('central.media.index'))->assertRedirect('/admin/login');
    }

    /** @return list<string> */
    private function centralUrls(): array
    {
        return [
            CentralProductResource::getUrl('index'),
            CentralCategoryResource::getUrl('index'),
            CentralBrandResource::getUrl('index'),
            MarketResource::getUrl('index'),
            CatalogSnapshotResource::getUrl('index'),
            route('central.media.index'),
        ];
    }
}
