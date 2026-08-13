<?php

declare(strict_types=1);

namespace Tests\Unit\Navigation;

use App\Enums\UserRole;
use App\Models\User;
use App\Navigation\CentralNavigationRegistry;
use App\Support\DesignSystem\FoundationDesignSystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class CentralNavigationRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_has_the_deterministic_unique_central_contract(): void
    {
        $items = app(CentralNavigationRegistry::class)->items();

        $this->assertSame([
            'dashboard',
            'catalog',
            'brands',
            'imports',
            'media',
            'translations',
            'changes',
            'prices',
            'snapshots',
            'users',
            'system',
        ], array_column($items, 'id'));
        $this->assertCount(11, array_unique(array_column($items, 'id')));
        $this->assertCount(11, array_unique(array_column($items, 'route')));
        $this->assertCount(10, array_unique(array_column($items, 'permission')));
    }

    public function test_unavailable_and_unauthorized_items_never_become_dead_links(): void
    {
        $registry = app(CentralNavigationRegistry::class);
        $central = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $translator = User::factory()->create(['role' => UserRole::Translator]);

        $this->assertSame([
            'dashboard',
            'catalog',
            'brands',
            'imports',
            'media',
            'translations',
            'changes',
            'prices',
            'snapshots',
        ], array_column($registry->visibleItemsFor($central), 'id'));
        $this->assertSame([
            'dashboard',
            'translations',
        ], array_column($registry->visibleItemsFor($translator), 'id'));

        foreach ($registry->visibleItemsFor($central) as $item) {
            $this->assertStringStartsWith('/', $item['url']);
        }
    }

    public function test_catalog_navigation_matches_resource_permissions_children_and_icon_registry(): void
    {
        $registry = app(CentralNavigationRegistry::class);
        $catalogEditor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $route = Route::getRoutes()->getByName('filament.central.resources.central-products.edit');

        $this->assertNotNull($route);
        $this->app['request']->setRouteResolver(static fn () => $route);

        $groups = $registry->filamentNavigation($catalogEditor)->getNavigation();
        $catalog = collect($groups[0]->getItems())->first(
            static fn ($item): bool => $item->getLabel() === 'Catalog',
        );

        $this->assertNotNull($catalog);
        $this->assertSame(FoundationDesignSystem::HEROICON_COMPONENTS['squares-2x2'], $catalog->getIcon());
        $this->assertTrue($catalog->isActive());

        $brands = collect($groups[0]->getItems())->first(
            static fn ($item): bool => $item->getLabel() === 'Brands',
        );

        $this->assertNotNull($brands);
        $this->assertFalse($brands->isActive());
    }

    public function test_brands_navigation_uses_catalog_permission_and_has_an_exclusive_active_state(): void
    {
        $registry = app(CentralNavigationRegistry::class);
        $catalogEditor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $translator = User::factory()->create(['role' => UserRole::Translator]);
        $route = Route::getRoutes()->getByName('filament.central.resources.brands.index');

        $this->assertNotNull($route);
        $this->app['request']->setRouteResolver(static fn () => $route);

        $items = collect($registry->filamentNavigation($catalogEditor)->getNavigation()[0]->getItems());
        $catalog = $items->first(static fn ($item): bool => $item->getLabel() === 'Catalog');
        $brands = $items->first(static fn ($item): bool => $item->getLabel() === 'Brands');

        $this->assertNotNull($catalog);
        $this->assertNotNull($brands);
        $this->assertFalse($catalog->isActive());
        $this->assertTrue($brands->isActive());
        $this->assertSame('/admin/central/brands', $brands->getUrl());
        $this->assertContains('brands', array_column($registry->visibleItemsFor($catalogEditor), 'id'));
        $this->assertNotContains('brands', array_column($registry->visibleItemsFor($translator), 'id'));
    }
}
