<?php

declare(strict_types=1);

namespace Tests\Feature\Sites;

use App\Enums\SiteStatus;
use App\Exceptions\Sites\UnknownSiteException;
use App\Models\Site;
use App\Services\Sites\SiteContextValueResolver;
use App\Services\Sites\SiteResolver;
use Database\Seeders\SiteFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SiteRuntimeContextIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_active_foundation_sites_resolve_independently_with_exact_regional_values(): void
    {
        $this->seed(SiteFoundationSeeder::class);
        $resolver = app(SiteResolver::class);
        $values = app(SiteContextValueResolver::class);
        $techDomain = $resolver->resolveDomain(SiteFoundationSeeder::TECH_HOST);
        $monitorsDomain = $resolver->resolveDomain(SiteFoundationSeeder::MONITORS_HOST);
        $tech = $values->resolve($techDomain->site, $techDomain, 'en-DE');
        $monitors = $values->resolve($monitorsDomain->site, $monitorsDomain, null);

        self::assertNotSame($tech->site->id, $monitors->site->id);
        self::assertSame('tech-germany', $tech->site->code);
        self::assertSame('monitors-germany', $monitors->site->code);
        self::assertSame('en-DE', $tech->resolvedLocale);
        self::assertSame('de-DE', $monitors->resolvedLocale);

        foreach ([$tech, $monitors] as $context) {
            self::assertSame('EUR', $context->currencyCode);
            self::assertSame('Europe/Berlin', $context->timezone);
            self::assertSame(['de-DE', 'en-DE'], $context->site->locales()->ordered()->pluck('locale_code')->all());
            self::assertSame(0, $context->site->categories()->count());
            self::assertSame(0, $context->site->products()->count());
        }
    }

    public function test_archived_fixture_is_unavailable_and_seeding_is_idempotent(): void
    {
        $this->seed(SiteFoundationSeeder::class);
        $this->seed(SiteFoundationSeeder::class);

        self::assertSame(3, Site::query()->whereIn('code', [
            'tech-germany',
            'monitors-germany',
            'archived-germany',
        ])->count());
        self::assertSame(2, Site::query()->where('status', SiteStatus::Active)->count());

        $this->expectException(UnknownSiteException::class);

        app(SiteResolver::class)->resolve(SiteFoundationSeeder::ARCHIVED_HOST);
    }
}
