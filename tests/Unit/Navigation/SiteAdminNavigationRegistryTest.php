<?php

declare(strict_types=1);

namespace Tests\Unit\Navigation;

use App\Models\Site;
use App\Models\User;
use App\Navigation\SiteAdminNavigationRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

final class SiteAdminNavigationRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_has_deterministic_ids_permissions_features_and_routes(): void
    {
        $items = app(SiteAdminNavigationRegistry::class)->items();

        $this->assertSame([
            'dashboard',
            'settings',
            'categories',
            'products',
            'theme',
            'sync',
            'corrections',
            'prices',
            'reviews',
            'leads',
            'content',
            'polls',
        ], array_column($items, 'id'));
        $this->assertCount(12, array_unique(array_column($items, 'id')));
        $this->assertCount(12, array_unique(array_column($items, 'route')));
        $this->assertContains('theme', array_column($items, 'feature'));
        $this->assertNotContains('', array_column($items, 'permission'));
    }

    public function test_only_authorized_implemented_destinations_are_visible_and_site_scoped(): void
    {
        $site = Site::factory()->active()->withRuntimeContext()->create();
        $user = User::factory()->siteAdmin($site)->create();
        $items = app(SiteAdminNavigationRegistry::class)->visibleItemsFor($user, $site);

        $this->assertSame(['dashboard'], array_column($items, 'id'));
        $this->assertStringContainsString('/admin/site', $items[0]['url']);
        $this->assertStringContainsString('site_id='.$site->getKey(), $items[0]['url']);
        $this->assertSame('available', $items[0]['state']);
    }

    public function test_unassigned_user_gets_no_site_navigation(): void
    {
        $site = Site::factory()->active()->withRuntimeContext()->create();
        $user = User::factory()->create();

        $this->assertSame([], app(SiteAdminNavigationRegistry::class)->visibleItemsFor($user, $site));
    }

    public function test_index_destinations_keep_their_section_active_on_child_routes(): void
    {
        $method = new ReflectionMethod(SiteAdminNavigationRegistry::class, 'activeRoutePattern');

        $this->assertSame('site.products.*', $method->invoke(null, 'site.products.index'));
        $this->assertSame('filament.site.pages.home', $method->invoke(null, 'filament.site.pages.home'));
    }
}
