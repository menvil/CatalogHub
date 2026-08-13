<?php

declare(strict_types=1);

namespace App\Navigation;

use App\Enums\Permission;
use App\Models\User;
use App\Support\DesignSystem\FoundationDesignSystem;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Route;
use LogicException;

final class CentralNavigationRegistry
{
    /**
     * @return list<array{id: string, label: string, icon: string, route: string, permission: string, state: string, url: null}>
     */
    public function items(): array
    {
        return [
            $this->item('dashboard', 'Dashboard', 'home', 'filament.central.pages.home', Permission::CentralPageAccess, 'available'),
            $this->item('catalog', 'Catalog', 'squares-2x2', 'filament.central.resources.central-products.index', Permission::CatalogProductsManage, 'available'),
            $this->item('brands', 'Brands', 'tag', 'central.brands.index', Permission::CatalogProductsManage, 'available'),
            $this->item('imports', 'Imports', 'arrow-up-tray', 'filament.central.resources.import-batches.index', Permission::ImportsManage, 'available'),
            $this->item('media', 'Media', 'photo', 'central.media.index', Permission::MediaManage, 'available'),
            $this->item('translations', 'Translations', 'language', 'central.translations.dashboard', Permission::TranslationsManage, 'available'),
            $this->item('changes', 'Changes', 'inbox-stack', 'filament.central.resources.change-requests.index', Permission::CorrectionsReview, 'available'),
            $this->item('prices', 'Prices', 'currency-dollar', 'filament.central.resources.price-sources.index', Permission::PricesManage, 'available'),
            $this->item('snapshots', 'Snapshots', 'archive-box', 'filament.central.resources.snapshots.index', Permission::BackupsManage, 'available'),
            $this->item('users', 'Users', 'users', 'central.users.index', Permission::CentralManage, 'unavailable'),
            $this->item('system', 'System', 'cog-6-tooth', 'central.system.index', Permission::CentralPanelAccess, 'unavailable'),
        ];
    }

    /**
     * @return list<array{id: string, label: string, icon: string, route: string, permission: string, state: string, url: string}>
     */
    public function visibleItemsFor(User $user): array
    {
        return array_values(array_map(
            static fn (array $item): array => [...$item, 'url' => route($item['route'], absolute: false)],
            array_filter(
                $this->items(),
                static fn (array $item): bool => $item['state'] === 'available'
                    && Route::has($item['route'])
                    && $user->can($item['permission']),
            ),
        ));
    }

    public function filamentNavigation(?User $user): NavigationBuilder
    {
        $builder = new NavigationBuilder;

        if (! $user instanceof User) {
            return $builder;
        }

        foreach ($this->visibleItemsFor($user) as $item) {
            $builder->item(
                NavigationItem::make($item['label'])
                    ->key('central-'.$item['id'])
                    ->icon(self::iconComponent($item['icon']))
                    ->url($item['url'])
                    ->isActiveWhen(static fn (): bool => request()->routeIs(self::activeRoutePattern($item['route']))),
            );
        }

        return $builder;
    }

    /**
     * @return array{id: string, label: string, icon: string, route: string, permission: string, state: string, url: null}
     */
    private function item(
        string $id,
        string $label,
        string $icon,
        string $route,
        Permission $permission,
        string $state,
    ): array {
        return [
            'id' => $id,
            'label' => $label,
            'icon' => $icon,
            'route' => $route,
            'permission' => $permission->value,
            'state' => $state,
            'url' => null,
        ];
    }

    private static function activeRoutePattern(string $route): string
    {
        return str_ends_with($route, '.index')
            ? substr($route, 0, -strlen('.index')).'.*'
            : $route;
    }

    private static function iconComponent(string $icon): string
    {
        return FoundationDesignSystem::HEROICON_COMPONENTS[$icon]
            ?? throw new LogicException("Unknown Central navigation icon [{$icon}].");
    }
}
