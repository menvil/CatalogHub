<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SiteResource;
use App\Filament\Resources\SiteResource\Pages\BrandVisibilityRules;
use App\Filament\Resources\SiteResource\Pages\EditSite;
use App\Filament\Resources\SiteResource\Pages\ListSites;
use App\Filament\Resources\SiteResource\Pages\LocalOverrideEditor;
use App\Filament\Resources\SiteResource\Pages\LocalSeoOverride;
use App\Filament\Resources\SiteResource\Pages\ManageSiteProducts;
use App\Filament\Resources\SiteResource\Pages\SiteDashboard;
use App\Filament\Resources\SiteResource\RelationManagers\SiteFeaturesRelationManager;
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
                'default_locale' => $tooLong,
            ])
            ->call('save')
            ->assertHasFormErrors([
                'code' => 'max',
                'name' => 'max',
                'domain' => 'max',
                'default_locale' => 'max',
            ]);
    }
}
