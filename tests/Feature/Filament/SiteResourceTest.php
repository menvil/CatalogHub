<?php

namespace Tests\Feature\Filament;

use App\Enums\SiteMode;
use App\Enums\UserRole;
use App\Filament\Resources\SiteResource;
use App\Filament\Resources\SiteResource\Pages\BrandVisibilityRules;
use App\Filament\Resources\SiteResource\Pages\EditSite;
use App\Filament\Resources\SiteResource\Pages\ListSites;
use App\Filament\Resources\SiteResource\Pages\LocalOverrideEditor;
use App\Filament\Resources\SiteResource\Pages\LocalSeoOverride;
use App\Filament\Resources\SiteResource\Pages\ManageSiteProducts;
use App\Filament\Resources\SiteResource\Pages\SiteDashboard;
use App\Filament\Resources\SiteResource\RelationManagers\SiteFeaturesRelationManager;
use App\Models\Market;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_site_resource_and_crud_pages(): void
    {
        $this->assertTrue(class_exists(SiteResource::class));
        $this->assertSame(Site::class, SiteResource::getModel());
        $this->assertArrayHasKey('index', SiteResource::getPages());
        $this->assertArrayNotHasKey('create', SiteResource::getPages());
        $this->assertArrayHasKey('edit', SiteResource::getPages());
        $this->assertTrue(class_exists(ListSites::class));
        $this->assertTrue(class_exists(EditSite::class));
        $this->assertTrue(class_exists(ManageSiteProducts::class));
        $this->assertArrayHasKey('products', SiteResource::getPages());
        $this->assertTrue(class_exists(BrandVisibilityRules::class));
        $this->assertArrayHasKey('brands', SiteResource::getPages());
        $this->assertTrue(class_exists(LocalOverrideEditor::class));
        $this->assertArrayHasKey('overrides', SiteResource::getPages());
        $this->assertTrue(class_exists(LocalSeoOverride::class));
        $this->assertArrayHasKey('seo', SiteResource::getPages());
        $this->assertTrue(class_exists(SiteDashboard::class));
        $this->assertArrayHasKey('dashboard', SiteResource::getPages());
        $this->assertContains(SiteFeaturesRelationManager::class, SiteResource::getRelations());
    }

    public function test_generic_create_route_is_not_available(): void
    {
        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(SiteResource::getUrl().'/create')
            ->assertNotFound();
    }

    public function test_site_string_fields_enforce_database_length_limits(): void
    {
        $site = Site::factory()->create();
        $tooLong = str_repeat('x', 256);

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(EditSite::class, ['record' => $site->getRouteKey()])
            ->fillForm([
                'code' => $tooLong,
                'name' => $tooLong,
                'domain' => $tooLong,
            ])
            ->call('save')
            ->assertHasFormErrors([
                'code' => 'max',
                'name' => 'max',
                'domain' => 'max',
            ]);
    }

    public function test_user_without_site_settings_permission_cannot_edit_site(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->create(['role' => UserRole::CatalogEditor]))
            ->get(EditSite::getUrl(['record' => $site]))
            ->assertForbidden();
    }

    public function test_invariant_sensitive_site_fields_are_immutable_on_edit(): void
    {
        $site = Site::factory()->create([
            'mode' => SiteMode::MultiCategory,
            'default_locale' => 'en-US',
        ]);
        $originalMarketId = $site->market_id;
        $alternateMarket = Market::factory()->create();

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(EditSite::class, ['record' => $site->getRouteKey()])
            ->fillForm([
                'market_id' => $alternateMarket->id,
                'mode' => SiteMode::SingleCategory->value,
                'default_locale' => 'de-DE',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $site->refresh();
        $this->assertSame($originalMarketId, $site->market_id);
        $this->assertSame(SiteMode::MultiCategory, $site->mode);
        $this->assertSame('en-US', $site->default_locale);
    }

    public function test_user_without_sites_permission_cannot_list_or_view_sites(): void
    {
        $site = Site::factory()->create();
        $user = User::factory()->create(['role' => UserRole::CatalogEditor]);

        $this->actingAs($user)
            ->get(SiteResource::getUrl())
            ->assertForbidden();

        $this->actingAs($user)
            ->get(SiteDashboard::getUrl(['record' => $site]))
            ->assertForbidden();
    }

    public function test_authorized_site_manager_can_list_sites_and_open_the_wizard(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::SiteAdmin]))
            ->get(SiteResource::getUrl())
            ->assertOk()
            ->assertSee('Create site');

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(SiteResource::getUrl())
            ->assertOk()
            ->assertSee('Create site');

        $this->assertFalse(SiteResource::canCreate());
    }
}
