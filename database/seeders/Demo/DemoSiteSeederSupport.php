<?php

namespace Database\Seeders\Demo;

use App\Enums\CategorySchemaStatus;
use App\Enums\CentralCategoryStatus;
use App\Enums\MarketStatus;
use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Enums\ThemeStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\LayoutTemplate;
use App\Models\Locale;
use App\Models\Market;
use App\Models\Site;
use App\Models\SiteHomeBlock;
use App\Models\Theme;
use App\Models\ThemeManifestRecord;
use Illuminate\Support\Facades\DB;

final class DemoSiteSeederSupport
{
    /** @var array<string, string> */
    private const LAYOUTS = [
        'home' => 'default-home',
        'category' => 'default-category',
        'listing' => 'default-listing',
        'product' => 'default-product',
        'compare' => 'default-compare',
        'article' => 'default-article',
        'search' => 'default-search',
    ];

    /** @return array<string, CentralCategory> */
    public function categories(): array
    {
        $definitions = [
            'monitors' => 'Monitors',
            'keyboards' => 'Keyboards',
            'mice' => 'Mice',
        ];

        $categories = [];

        foreach ($definitions as $slug => $name) {
            $categories[$slug] = CentralCategory::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'status' => CentralCategoryStatus::Active,
                    'schema_status' => CategorySchemaStatus::Approved,
                    'position' => array_search($slug, array_keys($definitions), true),
                ],
            );
        }

        return $categories;
    }

    public function market(): Market
    {
        Locale::query()->updateOrCreate(
            ['code' => 'en-US'],
            [
                'language_code' => 'en',
                'region_code' => 'US',
                'name' => 'English (United States)',
                'native_name' => 'English (United States)',
                'direction' => 'ltr',
                'is_active' => true,
                'position' => 0,
            ],
        );

        return Market::query()->updateOrCreate(
            ['code' => 'GLOBAL-DEMO'],
            [
                'name' => 'Global Demo Market',
                'country_code' => 'US',
                'currency_code' => 'USD',
                'default_locale' => 'en-US',
                'timezone' => 'UTC',
                'status' => MarketStatus::Active,
                'config_json' => ['demo' => true],
            ],
        );
    }

    public function theme(): Theme
    {
        $theme = Theme::query()->updateOrCreate(
            ['code' => 'cataloghub-demo'],
            [
                'name' => 'CatalogHub Demo',
                'description' => 'Clean public theme for deterministic demo sites.',
                'status' => ThemeStatus::Active,
                'version' => '1.0.0',
                'is_system' => true,
                'config_json' => ['public_layout' => 'public.layouts.app'],
            ],
        );
        $supports = ['hero_search', 'popular_categories', 'top_products', 'buying_guides', 'lead_form'];
        $manifestLayouts = array_filter(
            self::LAYOUTS,
            fn (string $pageType): bool => $pageType !== 'listing',
            ARRAY_FILTER_USE_KEY,
        );

        ThemeManifestRecord::query()->updateOrCreate(
            ['theme_id' => $theme->id],
            [
                'manifest_json' => [
                    'code' => $theme->code,
                    'name' => $theme->name,
                    'supports' => $supports,
                    'layouts' => $manifestLayouts,
                    'version' => '1.0.0',
                ],
                'supports_json' => $supports,
                'layouts_json' => $manifestLayouts,
                'schema_version' => '1.0',
                'validated_at' => now(),
                'validation_errors_json' => [],
            ],
        );

        foreach (self::LAYOUTS as $pageType => $code) {
            LayoutTemplate::query()->updateOrCreate(
                ['theme_id' => $theme->id, 'page_type' => $pageType, 'code' => $code],
                [
                    'name' => str($pageType)->headline().' Default',
                    'view_path' => $pageType === 'article'
                        ? 'public.content.show'
                        : "public.pages.{$pageType}",
                    'slots_json' => ['main'],
                    'status' => 'active',
                ],
            );
        }

        return $theme;
    }

    /**
     * @param  list<string>  $categorySlugs
     * @param  list<array{code: string, config: array<string, mixed>}>  $blocks
     */
    public function site(
        string $code,
        string $name,
        string $domain,
        SiteMode $mode,
        array $categorySlugs,
        array $blocks,
    ): Site {
        $market = $this->market();
        $theme = $this->theme();
        $categories = $this->categories();
        $site = Site::query()->updateOrCreate(
            ['code' => $code],
            [
                'market_id' => $market->id,
                'theme_id' => $theme->id,
                'name' => $name,
                'domain' => $domain,
                'mode' => $mode,
                'default_locale' => 'en-US',
                'status' => SiteStatus::Active,
                'settings_json' => ['demo' => true, 'hero_title' => $name],
            ],
        );

        DB::table('site_locales')->updateOrInsert(
            ['site_id' => $site->id, 'locale_code' => 'en-US'],
            ['is_default' => true, 'is_enabled' => true, 'position' => 0, 'created_at' => now(), 'updated_at' => now()],
        );

        DB::transaction(function () use ($categories, $categorySlugs, $site): void {
            DB::table('site_categories')->where('site_id', $site->id)->delete();
            foreach ($categorySlugs as $position => $slug) {
                DB::table('site_categories')->insert([
                    'site_id' => $site->id,
                    'central_category_id' => $categories[$slug]->id,
                    'is_enabled' => true,
                    'position' => $position,
                    'settings_json' => json_encode(['demo' => true], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        foreach ($blocks as $position => $block) {
            SiteHomeBlock::query()->updateOrCreate(
                ['site_id' => $site->id, 'position' => $position],
                [
                    'block_code' => $block['code'],
                    'enabled' => true,
                    'config_json' => $block['config'],
                ],
            );
        }
        $site->homeBlocks()->where('position', '>=', count($blocks))->delete();

        return $site;
    }
}
