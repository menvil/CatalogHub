<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MarketStatus;
use App\Enums\PublicThemeId;
use App\Enums\SiteDomainType;
use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Models\Locale;
use App\Models\Market;
use App\Models\Site;
use App\Models\SiteDomain;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class SiteFoundationSeeder extends Seeder
{
    public const SITE_CODES = ['tech-germany', 'monitors-germany', 'archived-germany'];

    public const ACTIVE_SITE_CODES = ['tech-germany', 'monitors-germany'];

    public const TECH_HOST = 'tech-germany.test';

    public const TECH_ALIAS_HOST = 'www.tech-germany.test';

    public const MONITORS_HOST = 'monitors-germany.test';

    public const MONITORS_ALIAS_HOST = 'www.monitors-germany.test';

    public const ARCHIVED_HOST = 'archived-germany.test';

    private const FIXTURE_HOSTS = [
        self::TECH_HOST,
        self::TECH_ALIAS_HOST,
        self::MONITORS_HOST,
        self::MONITORS_ALIAS_HOST,
        self::ARCHIVED_HOST,
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->upsertLocales();
            $market = Market::query()->updateOrCreate(
                ['code' => 'DE-FOUNDATION'],
                [
                    'name' => 'Germany Foundation Market',
                    'country_code' => 'DE',
                    'currency_code' => 'EUR',
                    'default_locale' => 'de-DE',
                    'timezone' => 'Europe/Berlin',
                    'status' => MarketStatus::Active,
                    'config_json' => ['foundation_fixture' => true],
                ],
            );

            $this->upsertSite($market, 'tech-germany', 'Tech Germany', self::TECH_HOST, self::TECH_ALIAS_HOST, SiteMode::MultiCategory, SiteStatus::Active);
            $this->upsertSite($market, 'monitors-germany', 'Monitors Germany', self::MONITORS_HOST, self::MONITORS_ALIAS_HOST, SiteMode::SingleCategory, SiteStatus::Active);
            $this->upsertSite($market, 'archived-germany', 'Archived Germany', self::ARCHIVED_HOST, null, SiteMode::MultiCategory, SiteStatus::Archived);
        });
    }

    private function upsertLocales(): void
    {
        foreach ([
            ['code' => 'de-DE', 'language' => 'de', 'region' => 'DE', 'name' => 'Deutsch (Deutschland)', 'position' => 0],
            ['code' => 'en-DE', 'language' => 'en', 'region' => 'DE', 'name' => 'English (Germany)', 'position' => 1],
        ] as $locale) {
            Locale::query()->updateOrCreate(
                ['code' => $locale['code']],
                [
                    'language_code' => $locale['language'],
                    'region_code' => $locale['region'],
                    'name' => $locale['name'],
                    'native_name' => $locale['name'],
                    'direction' => 'ltr',
                    'is_active' => true,
                    'position' => $locale['position'],
                ],
            );
        }
    }

    private function upsertSite(
        Market $market,
        string $code,
        string $name,
        string $host,
        ?string $aliasHost,
        SiteMode $mode,
        SiteStatus $status,
    ): void {
        $normalizedHost = SiteDomain::normalizeHost($host);
        $theme = $mode === SiteMode::SingleCategory
            ? PublicThemeId::SingleCategory
            : PublicThemeId::MultiCategory;
        $site = Site::query()->updateOrCreate(
            ['code' => $code],
            [
                'market_id' => $market->id,
                'name' => $name,
                'domain' => $normalizedHost,
                'mode' => $mode,
                'default_locale' => 'de-DE',
                'currency_code' => 'EUR',
                'timezone' => 'Europe/Berlin',
                'status' => $status,
                'settings_json' => [
                    'foundation_fixture' => true,
                    'public_theme_id' => $theme->value,
                    'url_scheme' => 'https',
                    'seo' => [
                        'meta_title' => $name,
                        'meta_description' => "Deterministic foundation shell for {$name}.",
                    ],
                ],
            ],
        );
        $site->domains()
            ->where('host', '!=', $normalizedHost)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
        $site->domains()->updateOrCreate(
            ['host' => $normalizedHost],
            [
                'type' => SiteDomainType::Primary,
                'is_primary' => true,
                'is_active' => true,
            ],
        );

        $allowedHosts = [$normalizedHost];

        if ($aliasHost !== null) {
            $normalizedAlias = SiteDomain::normalizeHost($aliasHost);
            $allowedHosts[] = $normalizedAlias;
            $site->domains()->updateOrCreate(
                ['host' => $normalizedAlias],
                [
                    'type' => SiteDomainType::Alias,
                    'is_primary' => false,
                    'is_active' => true,
                ],
            );
        }

        $site->domains()
            ->whereIn('host', self::FIXTURE_HOSTS)
            ->whereNotIn('host', $allowedHosts)
            ->delete();
        $site->locales()->update(['is_default' => false]);

        foreach (['de-DE', 'en-DE'] as $position => $locale) {
            $site->locales()->updateOrCreate(
                ['locale_code' => $locale],
                [
                    'is_default' => $locale === 'de-DE',
                    'is_enabled' => true,
                    'position' => $position,
                ],
            );
        }
    }
}
