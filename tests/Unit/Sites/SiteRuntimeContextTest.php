<?php

declare(strict_types=1);

namespace Tests\Unit\Sites;

use App\Models\Market;
use App\Models\Site;
use App\Models\SiteDomain;
use App\Support\Sites\SiteRuntimeContext;
use Error;
use InvalidArgumentException;
use ReflectionClass;
use Tests\TestCase;

final class SiteRuntimeContextTest extends TestCase
{
    public function test_context_is_readonly_typed_and_has_no_http_request_dependency(): void
    {
        $context = $this->context();
        $reflection = new ReflectionClass($context);

        self::assertTrue($reflection->isReadOnly());
        self::assertSame('de-DE', $context->requestedLocale);
        self::assertSame('de-DE', $context->resolvedLocale);
        self::assertSame('EUR', $context->currencyCode);
        self::assertSame('Europe/Berlin', $context->timezone);
        self::assertNotContains(
            'Illuminate\Http\Request',
            array_map(
                static fn ($parameter): ?string => $parameter->getType()?->getName(),
                $reflection->getConstructor()?->getParameters() ?? [],
            ),
        );

        $this->expectException(Error::class);
        $context->resolvedLocale = 'en-DE';
    }

    public function test_missing_required_values_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SiteRuntimeContext(
            site: $this->site(),
            domain: $this->domain(),
            market: $this->market(),
            requestedLocale: null,
            resolvedLocale: '',
            currencyCode: 'EUR',
            timezone: 'Europe/Berlin',
        );
    }

    public function test_debug_output_is_whitelisted_and_contains_no_settings_or_secrets(): void
    {
        $debug = $this->context()->__debugInfo();

        self::assertSame([
            'site_id',
            'site_code',
            'host',
            'domain_type',
            'market_code',
            'requested_locale',
            'resolved_locale',
            'currency_code',
            'timezone',
        ], array_keys($debug));
        self::assertStringNotContainsString('secret', json_encode($debug, JSON_THROW_ON_ERROR));
    }

    private function context(): SiteRuntimeContext
    {
        return new SiteRuntimeContext(
            site: $this->site(),
            domain: $this->domain(),
            market: $this->market(),
            requestedLocale: 'de-DE',
            resolvedLocale: 'de-DE',
            currencyCode: 'EUR',
            timezone: 'Europe/Berlin',
        );
    }

    private function site(): Site
    {
        $site = new Site([
            'market_id' => 20,
            'code' => 'tech-de',
            'name' => 'Tech Germany',
            'default_locale' => 'de-DE',
            'currency_code' => 'EUR',
            'timezone' => 'Europe/Berlin',
            'settings_json' => ['api_secret' => 'secret-value'],
        ]);
        $site->setAttribute('id', 10);

        return $site;
    }

    private function domain(): SiteDomain
    {
        $domain = new SiteDomain([
            'site_id' => 10,
            'host' => 'tech-germany.test',
            'type' => 'primary',
            'is_primary' => true,
            'is_active' => true,
        ]);
        $domain->setAttribute('id', 30);

        return $domain;
    }

    private function market(): Market
    {
        $market = new Market([
            'code' => 'DE',
            'name' => 'Germany',
            'currency_code' => 'EUR',
            'default_locale' => 'de-DE',
            'timezone' => 'Europe/Berlin',
        ]);
        $market->setAttribute('id', 20);

        return $market;
    }
}
