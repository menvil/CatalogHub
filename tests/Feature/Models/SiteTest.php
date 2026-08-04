<?php

namespace Tests\Feature\Models;

use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Models\Market;
use App\Models\Site;
use App\Models\SiteDomain;
use App\Models\SiteLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_site_for_market_with_typed_state(): void
    {
        $market = Market::factory()->create();
        $site = Site::factory()->for($market)->create(['mode' => SiteMode::SingleCategory, 'status' => SiteStatus::Active]);

        $this->assertTrue($site->market->is($market));
        $this->assertTrue($site->isSingleCategory());
        $this->assertTrue($site->isActive());
    }

    public function test_site_mode_helpers_are_mutually_exclusive(): void
    {
        $site = Site::factory()->create(['mode' => SiteMode::MultiCategory, 'status' => SiteStatus::Archived]);

        $this->assertTrue($site->isMultiCategory());
        $this->assertFalse($site->isSingleCategory());
        $this->assertFalse($site->isActive());
    }

    public function test_runtime_relations_and_helpers_resolve_primary_domain_and_default_locale(): void
    {
        $site = Site::factory()->active()->withRuntimeContext(['de-DE', 'en-DE'], 'de-DE')->create();

        self::assertInstanceOf(SiteDomain::class, $site->primaryDomain);
        self::assertSame($site->domain, $site->primaryDomain->host);
        self::assertCount(1, $site->domains);
        self::assertInstanceOf(SiteLocale::class, $site->defaultLocale);
        self::assertSame('de-DE', $site->defaultLocale->locale_code);
        self::assertSame(['de-DE', 'en-DE'], $site->locales()->ordered()->pluck('locale_code')->all());
    }

    public function test_foundation_scopes_filter_by_status_and_code(): void
    {
        $draft = Site::factory()->create(['code' => 'draft-site', 'status' => SiteStatus::Draft]);
        $active = Site::factory()->create(['code' => 'active-site', 'status' => SiteStatus::Active]);
        $suspended = Site::factory()->create(['code' => 'suspended-site', 'status' => SiteStatus::Suspended]);
        Site::factory()->create(['code' => 'archived-site', 'status' => SiteStatus::Archived]);

        self::assertTrue(Site::query()->active()->sole()->is($active));
        self::assertEqualsCanonicalizing(
            [$draft->id, $active->id, $suspended->id],
            Site::query()->administrable()->pluck('id')->all(),
        );
        self::assertTrue(Site::query()->byCode('active-site')->sole()->is($active));
    }
}
