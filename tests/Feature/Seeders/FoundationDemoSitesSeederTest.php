<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Enums\PublicThemeId;
use App\Enums\SiteDomainType;
use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Exceptions\Sites\UnknownSiteException;
use App\Models\Site;
use App\Services\Sites\SiteResolver;
use Database\Seeders\SiteFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FoundationDemoSitesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_exact_site_theme_domain_and_runtime_configuration_without_catalog_data(): void
    {
        $this->seed(SiteFoundationSeeder::class);

        $expected = [
            'archived-germany' => [SiteStatus::Archived, SiteMode::MultiCategory, PublicThemeId::MultiCategory, [SiteFoundationSeeder::ARCHIVED_HOST]],
            'monitors-germany' => [SiteStatus::Active, SiteMode::SingleCategory, PublicThemeId::SingleCategory, [SiteFoundationSeeder::MONITORS_HOST, SiteFoundationSeeder::MONITORS_ALIAS_HOST]],
            'tech-germany' => [SiteStatus::Active, SiteMode::MultiCategory, PublicThemeId::MultiCategory, [SiteFoundationSeeder::TECH_HOST, SiteFoundationSeeder::TECH_ALIAS_HOST]],
        ];

        foreach ($expected as $code => [$status, $mode, $theme, $hosts]) {
            $site = Site::query()->where('code', $code)->sole();

            self::assertSame($status, $site->status);
            self::assertSame($mode, $site->mode);
            self::assertSame('de-DE', $site->default_locale);
            self::assertSame('EUR', $site->currency_code);
            self::assertSame('Europe/Berlin', $site->timezone);
            self::assertSame($theme->value, data_get($site->settings_json, 'public_theme_id'));
            self::assertSame(['de-DE', 'en-DE'], $site->locales()->ordered()->pluck('locale_code')->all());
            self::assertSame($hosts, $site->domains()->orderBy('host')->pluck('host')->all());
            self::assertSame(0, $site->categories()->count());
            self::assertSame(0, $site->products()->count());
            self::assertSame(0, $site->homeBlocks()->count());
        }
    }

    public function test_aliases_resolve_to_their_site_while_archived_hosts_remain_unavailable(): void
    {
        $this->seed(SiteFoundationSeeder::class);
        $resolver = app(SiteResolver::class);

        $alias = $resolver->resolveDomain(SiteFoundationSeeder::TECH_ALIAS_HOST);
        self::assertSame('tech-germany', $alias->site->code);
        self::assertSame(SiteDomainType::Alias, $alias->type);

        $this->expectException(UnknownSiteException::class);
        $resolver->resolve(SiteFoundationSeeder::ARCHIVED_HOST);
    }
}
