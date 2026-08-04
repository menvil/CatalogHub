<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MarketStatus;
use App\Enums\SiteDomainType;
use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Models\Locale;
use App\Models\Market;
use App\Models\Site;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class SiteFoundationSeeder extends Seeder
{
    public const TECH_HOST = 'tech-germany.test';

    public const MONITORS_HOST = 'monitors-germany.test';

    public const ARCHIVED_HOST = 'archived-germany.test';

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

            $this->upsertSite($market, 'tech-germany', 'Tech Germany', self::TECH_HOST, SiteMode::MultiCategory, SiteStatus::Active);
            $this->upsertSite($market, 'monitors-germany', 'Monitors Germany', self::MONITORS_HOST, SiteMode::SingleCategory, SiteStatus::Active);
            $this->upsertSite($market, 'archived-germany', 'Archived Germany', self::ARCHIVED_HOST, SiteMode::MultiCategory, SiteStatus::Archived);
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
        SiteMode $mode,
        SiteStatus $status,
    ): void {
        $site = Site::query()->updateOrCreate(
            ['code' => $code],
            [
                'market_id' => $market->id,
                'name' => $name,
                'domain' => $host,
                'mode' => $mode,
                'default_locale' => 'de-DE',
                'currency_code' => 'EUR',
                'timezone' => 'Europe/Berlin',
                'status' => $status,
                'settings_json' => ['foundation_fixture' => true],
            ],
        );
        $site->domains()->updateOrCreate(
            ['host' => $host],
            [
                'type' => SiteDomainType::Primary,
                'is_primary' => true,
                'is_active' => true,
            ],
        );
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
