<?php

declare(strict_types=1);

namespace App\Support\DesignSystem;

use App\Enums\PublicThemeId;
use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Models\Site;
use App\Support\Seo\SeoMetadata;
use App\Support\Themes\PublicThemeContext;

final class PublicShellFixture
{
    public const VERSION = 'public-shell-v1';

    public const STATES = [
        'multi-desktop',
        'multi-mobile',
        'single-desktop',
        'single-mobile',
    ];

    /**
     * @return array{
     *     locale: string,
     *     localeOptions: list<array{code: string, label: string, url: string, current: bool}>,
     *     navigation: array{home: string, search: string},
     *     seo: SeoMetadata,
     *     site: Site,
     *     theme: PublicThemeContext
     * }
     */
    public static function context(string $state): array
    {
        $single = str_starts_with($state, 'single-');
        $themeId = $single ? PublicThemeId::SingleCategory : PublicThemeId::MultiCategory;
        $host = $single ? 'monitors.cataloghub.test' : 'tech.cataloghub.test';
        $name = $single ? 'Monitors Germany' : 'Tech Germany';
        $locale = 'de-DE';
        $site = new Site;
        $site->setRawAttributes([
            'id' => $single ? 8202 : 8201,
            'code' => $single ? 'monitors-germany' : 'tech-germany',
            'name' => $name,
            'domain' => $host,
            'mode' => $single ? SiteMode::SingleCategory->value : SiteMode::MultiCategory->value,
            'default_locale' => $locale,
            'currency_code' => 'EUR',
            'timezone' => 'Europe/Berlin',
            'status' => SiteStatus::Active->value,
            'settings_json' => [],
        ], true);
        $site->exists = true;

        return [
            'locale' => $locale,
            'localeOptions' => [
                ['code' => 'de-DE', 'label' => 'Deutsch (Deutschland)', 'url' => "https://{$host}/de-DE", 'current' => true],
                ['code' => 'en-DE', 'label' => 'English (Germany)', 'url' => "https://{$host}/en-DE", 'current' => false],
            ],
            'navigation' => [
                'home' => "https://{$host}/de-DE",
                'search' => "https://{$host}/de-DE/search",
            ],
            'seo' => new SeoMetadata(
                title: $name,
                description: 'Deterministic public shell acceptance fixture.',
                canonical: "https://{$host}/de-DE",
                alternates: [
                    'de-DE' => "https://{$host}/de-DE",
                    'en-DE' => "https://{$host}/en-DE",
                ],
            ),
            'site' => $site,
            'theme' => new PublicThemeContext(
                identifier: $themeId,
                layout: $themeId->layout(),
                config: ['header_variant' => $single ? 'focused' : 'catalog'],
                features: $single ? ['focused-navigation'] : ['category-navigation'],
            ),
        ];
    }

    private function __construct() {}
}
