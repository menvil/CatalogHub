<?php

declare(strict_types=1);

namespace App\Navigation;

use App\Contracts\Auth\SiteAdminAccess;
use App\Enums\Permission;
use App\Models\Site;
use App\Models\User;
use App\Support\DesignSystem\FoundationDesignSystem;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Route;
use LogicException;

final readonly class SiteAdminNavigationRegistry
{
    public function __construct(private SiteAdminAccess $access) {}

    /**
     * @return list<array{id: string, label: string, icon: string, route: string, permission: string, feature: string|null, state: string, url: null}>
     */
    public function items(): array
    {
        return [
            $this->item('dashboard', 'Dashboard', 'home', 'filament.site.pages.home', Permission::SitePageAccess, null, 'available'),
            $this->item('settings', 'Settings', 'cog-6-tooth', 'site.settings.index', Permission::SiteSettingsManage, null, 'unavailable'),
            $this->item('categories', 'Categories', 'squares-2x2', 'site.categories.index', Permission::SiteContentManage, 'categories', 'unavailable'),
            $this->item('products', 'Products', 'archive-box', 'site.products.index', Permission::SiteContentManage, 'products', 'unavailable'),
            $this->item('theme', 'Theme', 'pencil-square', 'site.theme.index', Permission::SiteSettingsManage, 'theme', 'unavailable'),
            $this->item('sync', 'Sync', 'arrow-up-tray', 'site.sync.index', Permission::SiteSettingsManage, 'sync', 'unavailable'),
            $this->item('corrections', 'Corrections', 'inbox-stack', 'site.corrections.index', Permission::CorrectionsRequest, 'corrections', 'unavailable'),
            $this->item('prices', 'Prices', 'currency-dollar', 'site.prices.index', Permission::PricesManage, 'prices', 'unavailable'),
            $this->item('reviews', 'Reviews', 'eye', 'site.reviews.index', Permission::ReviewsModerate, 'reviews', 'unavailable'),
            $this->item('leads', 'Leads', 'users', 'site.leads.index', Permission::LeadsManage, 'leads', 'unavailable'),
            $this->item('content', 'Content', 'language', 'site.content.index', Permission::SiteContentManage, 'content', 'unavailable'),
            $this->item('polls', 'Polls', 'information-circle', 'site.polls.index', Permission::SiteContentManage, 'polls', 'unavailable'),
        ];
    }

    /**
     * @return list<array{id: string, label: string, icon: string, route: string, permission: string, feature: string|null, state: string, url: string}>
     */
    public function visibleItemsFor(User $user, Site $site): array
    {
        if (! $this->access->allows($user, $site)) {
            return [];
        }

        return array_values(array_map(
            static fn (array $item): array => [
                ...$item,
                'url' => route($item['route'], ['site_id' => $site->getKey()], absolute: false),
            ],
            array_filter(
                $this->items(),
                static fn (array $item): bool => $item['state'] === 'available'
                    && Route::has($item['route'])
                    && $user->can($item['permission']),
            ),
        ));
    }

    public function filamentNavigation(?User $user, ?Site $site): NavigationBuilder
    {
        $builder = new NavigationBuilder;

        if (! $user instanceof User || ! $site instanceof Site) {
            return $builder;
        }

        foreach ($this->visibleItemsFor($user, $site) as $item) {
            $builder->item(
                NavigationItem::make($item['label'])
                    ->key('site-'.$item['id'])
                    ->icon(self::iconComponent($item['icon']))
                    ->url($item['url'])
                    ->isActiveWhen(static fn (): bool => request()->routeIs(self::activeRoutePattern($item['route']))),
            );
        }

        return $builder;
    }

    /**
     * @return array{id: string, label: string, icon: string, route: string, permission: string, feature: string|null, state: string, url: null}
     */
    private function item(
        string $id,
        string $label,
        string $icon,
        string $route,
        Permission $permission,
        ?string $feature,
        string $state,
    ): array {
        return [
            'id' => $id,
            'label' => $label,
            'icon' => $icon,
            'route' => $route,
            'permission' => $permission->value,
            'feature' => $feature,
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
            ?? throw new LogicException("Unknown Site Admin navigation icon [{$icon}].");
    }
}
