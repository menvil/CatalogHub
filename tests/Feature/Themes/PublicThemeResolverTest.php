<?php

declare(strict_types=1);

namespace Tests\Feature\Themes;

use App\Contracts\Themes\PublicThemeResolver as PublicThemeResolverContract;
use App\Enums\PublicLayoutType;
use App\Enums\PublicThemeId;
use App\Exceptions\Sites\UnknownSiteException;
use App\Models\Site;
use App\Services\Sites\SiteContextValueResolver;
use App\Services\Sites\SiteResolver;
use App\Support\Sites\SiteRuntimeContext;
use Database\Seeders\SiteFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

final class PublicThemeResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_foundation_sites_resolve_to_their_whitelisted_layouts(): void
    {
        $this->seed(SiteFoundationSeeder::class);
        $themes = app(PublicThemeResolverContract::class);
        $tech = $themes->resolve($this->contextFor(SiteFoundationSeeder::TECH_HOST));
        $monitors = $themes->resolve($this->contextFor(SiteFoundationSeeder::MONITORS_HOST));

        self::assertSame(PublicThemeId::MultiCategory, $tech->identifier);
        self::assertSame(PublicLayoutType::MultiCategory, $tech->layout);
        self::assertSame(PublicThemeId::SingleCategory, $monitors->identifier);
        self::assertSame(PublicLayoutType::SingleCategory, $monitors->layout);

        $this->get('http://'.SiteFoundationSeeder::TECH_HOST.'/de-DE')
            ->assertOk()
            ->assertSee('data-public-layout="multi-category"', false);
        $this->get('http://'.SiteFoundationSeeder::MONITORS_HOST.'/de-DE')
            ->assertOk()
            ->assertSee('data-public-layout="single-category"', false);
    }

    public function test_unknown_configured_theme_is_rejected_without_treating_it_as_a_view_path(): void
    {
        $this->seed(SiteFoundationSeeder::class);
        $site = Site::query()->where('code', 'tech-germany')->firstOrFail();
        $site->update(['settings_json' => ['public_theme_id' => '../../uploaded/theme.blade.php']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown public theme identifier [../../uploaded/theme.blade.php].');

        app(PublicThemeResolverContract::class)->resolve($this->contextFor(SiteFoundationSeeder::TECH_HOST));
    }

    public function test_archived_site_is_rejected_before_theme_resolution(): void
    {
        $this->seed(SiteFoundationSeeder::class);
        $this->expectException(UnknownSiteException::class);

        $this->contextFor(SiteFoundationSeeder::ARCHIVED_HOST);
    }

    private function contextFor(string $host): SiteRuntimeContext
    {
        $domain = app(SiteResolver::class)->resolveDomain($host);

        return app(SiteContextValueResolver::class)->resolve($domain->site, $domain, null);
    }
}
