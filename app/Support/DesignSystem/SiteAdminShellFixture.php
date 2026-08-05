<?php

declare(strict_types=1);

namespace App\Support\DesignSystem;

use App\Enums\MarketStatus;
use App\Enums\SiteDomainType;
use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Enums\UserRole;
use App\Models\Market;
use App\Models\Site;
use App\Models\SiteDomain;
use App\Models\User;
use App\Support\Sites\SiteRuntimeContext;
use Illuminate\Database\Eloquent\Model;

final class SiteAdminShellFixture
{
    public const VERSION = 'site-admin-shell-v1';

    public const STATES = ['default', 'mobile', 'one-site', 'multi-site'];

    public static function user(): User
    {
        return self::persistedModel(new User, [
            'id' => 5101,
            'name' => 'Site Acceptance User',
            'email' => 'site.acceptance@example.test',
            'role' => UserRole::SiteAdmin->value,
        ]);
    }

    /** @return array{current: Site, alternate: Site, context: SiteRuntimeContext} */
    public static function context(): array
    {
        $market = self::persistedModel(new Market, [
            'id' => 3101,
            'code' => 'DE',
            'name' => 'Germany',
            'country_code' => 'DE',
            'currency_code' => 'EUR',
            'default_locale' => 'de-DE',
            'timezone' => 'Europe/Berlin',
            'status' => MarketStatus::Active->value,
        ]);
        $current = self::site(4101, 'Tech Germany', 'tech.cataloghub.test', $market);
        $alternate = self::site(4102, 'Monitors Germany', 'monitors.cataloghub.test', $market);
        $domain = self::persistedModel(new SiteDomain, [
            'id' => 6101,
            'site_id' => $current->getKey(),
            'host' => 'tech.cataloghub.test',
            'type' => SiteDomainType::Primary->value,
            'is_primary' => true,
            'is_active' => true,
        ]);

        return [
            'current' => $current,
            'alternate' => $alternate,
            'context' => new SiteRuntimeContext(
                site: $current,
                domain: $domain,
                market: $market,
                requestedLocale: 'de-DE',
                resolvedLocale: 'de-DE',
                currencyCode: 'EUR',
                timezone: 'Europe/Berlin',
            ),
        ];
    }

    /** @return list<array{id: string, label: string, icon: string, url: string}> */
    public static function navigation(Site $site): array
    {
        return [[
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'home',
            'url' => route('filament.site.pages.home', ['site_id' => $site->getKey()], absolute: false),
        ]];
    }

    private static function site(int $id, string $name, string $host, Market $market): Site
    {
        return self::persistedModel(new Site, [
            'id' => $id,
            'market_id' => $market->getKey(),
            'code' => strtolower(str_replace(' ', '-', $name)),
            'name' => $name,
            'domain' => $host,
            'mode' => SiteMode::MultiCategory->value,
            'default_locale' => 'de-DE',
            'currency_code' => 'EUR',
            'timezone' => 'Europe/Berlin',
            'status' => SiteStatus::Active->value,
        ]);
    }

    /** @template TModel of \Illuminate\Database\Eloquent\Model
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    private static function persistedModel(Model $model, array $attributes): Model
    {
        $model->setRawAttributes($attributes, true);
        $model->exists = true;

        return $model;
    }

    private function __construct() {}
}
