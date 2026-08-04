<?php

declare(strict_types=1);

namespace Tests\Unit\Sites;

use App\Exceptions\Sites\InvalidSiteRuntimeConfigurationException;
use App\Models\Site;
use App\Models\SiteDomain;
use App\Services\Sites\SiteContextValueResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SiteContextValueResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_requested_locale_and_site_values_are_resolved_exactly(): void
    {
        [$site, $domain] = $this->configuredSite();

        $context = app(SiteContextValueResolver::class)->resolve($site, $domain, 'en-DE');

        self::assertSame('en-DE', $context->requestedLocale);
        self::assertSame('en-DE', $context->resolvedLocale);
        self::assertSame('EUR', $context->currencyCode);
        self::assertSame('Europe/Berlin', $context->timezone);
    }

    public function test_disabled_or_missing_requested_locale_falls_back_to_the_enabled_default(): void
    {
        [$site, $domain] = $this->configuredSite();
        $site->locales()->where('locale_code', 'en-DE')->update(['is_enabled' => false]);

        foreach (['en-DE', 'fr-FR', null] as $requestedLocale) {
            $context = app(SiteContextValueResolver::class)->resolve($site, $domain, $requestedLocale);

            self::assertSame($requestedLocale, $context->requestedLocale);
            self::assertSame('de-DE', $context->resolvedLocale);
        }
    }

    public function test_invalid_default_locale_configuration_fails_loudly(): void
    {
        [$site, $domain] = $this->configuredSite();
        $site->forceFill(['default_locale' => 'fr-FR'])->save();

        $this->expectException(InvalidSiteRuntimeConfigurationException::class);

        app(SiteContextValueResolver::class)->resolve($site, $domain, null);
    }

    #[DataProvider('invalidRegionalValues')]
    public function test_invalid_currency_or_timezone_fails_loudly(
        string $currencyCode,
        string $timezone,
    ): void {
        [$site, $domain] = $this->configuredSite();
        $site->forceFill(['currency_code' => $currencyCode, 'timezone' => $timezone])->save();

        $this->expectException(InvalidSiteRuntimeConfigurationException::class);

        app(SiteContextValueResolver::class)->resolve($site, $domain, null);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidRegionalValues(): iterable
    {
        yield 'lowercase currency' => ['eur', 'Europe/Berlin'];
        yield 'long currency' => ['EURO', 'Europe/Berlin'];
        yield 'unknown timezone' => ['EUR', 'Mars/Olympus'];
    }

    /** @return array{Site, SiteDomain} */
    private function configuredSite(): array
    {
        $site = Site::factory()->active()->withRuntimeContext(['de-DE', 'en-DE'], 'de-DE')->create([
            'domain' => 'values.example.test',
            'currency_code' => 'EUR',
            'timezone' => 'Europe/Berlin',
        ]);

        return [$site, $site->primaryDomain()->firstOrFail()];
    }
}
