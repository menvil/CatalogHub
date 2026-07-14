<?php

namespace Tests\Feature\Filament;

use App\Domains\Projections\SiteSyncService;
use App\Enums\CentralProductStatus;
use App\Enums\UserRole;
use App\Filament\Resources\SiteProductProjectionResource;
use App\Filament\Resources\SiteProductProjectionResource\Pages\ViewSiteProductProjection;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectionPreviewScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_editor_can_view_read_only_projection_preview(): void
    {
        $site = Site::factory()->create(['domain' => 'preview.example.test']);
        $product = CentralProduct::factory()->create([
            'name' => 'Projection Preview Product',
            'slug' => 'projection-preview-product',
            'status' => CentralProductStatus::Active,
        ]);
        $projection = app(SiteSyncService::class)->syncProduct($site, $product, 'en');

        $this->actingAs(User::factory()->create(['role' => UserRole::CatalogEditor]))
            ->get(ViewSiteProductProjection::getUrl(['record' => $projection]))
            ->assertOk()
            ->assertSee('Projection Preview Product')
            ->assertSee('Projection payload')
            ->assertSee('SEO payload')
            ->assertSee('Media payload')
            ->assertSee('Search document status')
            ->assertSee('Sitemap URL');
    }

    public function test_projection_resource_has_no_create_or_edit_pages(): void
    {
        $pages = SiteProductProjectionResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('view', $pages);
        $this->assertArrayNotHasKey('create', $pages);
        $this->assertArrayNotHasKey('edit', $pages);
    }

    public function test_site_admin_cannot_view_projection_preview(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create(['status' => CentralProductStatus::Active]);
        $projection = app(SiteSyncService::class)->syncProduct($site, $product, 'en');

        $this->actingAs(User::factory()->create(['role' => UserRole::SiteAdmin]))
            ->get(ViewSiteProductProjection::getUrl(['record' => $projection]))
            ->assertForbidden();
    }
}
