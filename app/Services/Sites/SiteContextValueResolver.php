<?php

declare(strict_types=1);

namespace App\Services\Sites;

use App\Exceptions\Sites\InvalidSiteRuntimeConfigurationException;
use App\Models\Market;
use App\Models\Site;
use App\Models\SiteDomain;
use App\Models\SiteLocale;
use App\Support\Sites\SiteRuntimeContext;
use DateTimeZone;
use Throwable;

final class SiteContextValueResolver
{
    public function resolve(
        Site $site,
        SiteDomain $domain,
        ?string $requestedLocale,
    ): SiteRuntimeContext {
        $site->loadMissing(['market', 'locales.locale']);
        $market = $this->market($site);
        $resolvedLocale = $this->resolvedLocale($site, $requestedLocale);
        [$currencyCode, $timezone] = $this->runtimeScalars($site);

        return new SiteRuntimeContext(
            site: $site,
            domain: $domain,
            market: $market,
            requestedLocale: $requestedLocale,
            resolvedLocale: $resolvedLocale,
            currencyCode: $currencyCode,
            timezone: $timezone,
        );
    }

    private function market(Site $site): Market
    {
        $market = $site->market;

        if (! $market instanceof Market) {
            throw $this->invalid($site, 'market is missing');
        }

        return $market;
    }

    private function resolvedLocale(Site $site, ?string $requestedLocale): string
    {
        $defaultLocale = $site->locales->first(
            static fn (SiteLocale $locale): bool => $locale->is_default,
        );

        if (! $defaultLocale instanceof SiteLocale
            || ! $defaultLocale->is_enabled
            || $defaultLocale->locale_code !== $site->default_locale
            || ! $defaultLocale->locale?->is_active) {
            throw $this->invalid($site, 'default locale must exist and be enabled');
        }

        $enabledLocales = $site->locales
            ->filter(static fn (SiteLocale $locale): bool => $locale->is_enabled && (bool) $locale->locale?->is_active)
            ->pluck('locale_code')
            ->all();

        return is_string($requestedLocale) && in_array($requestedLocale, $enabledLocales, true)
            ? $requestedLocale
            : $defaultLocale->locale_code;
    }

    /** @return array{string, string} */
    private function runtimeScalars(Site $site): array
    {
        $currencyCode = (string) $site->currency_code;
        $timezone = (string) $site->timezone;

        if (preg_match('/^[A-Z]{3}$/', $currencyCode) !== 1) {
            throw $this->invalid($site, 'currency code must use ISO 4217 format');
        }

        try {
            new DateTimeZone($timezone);
        } catch (Throwable) {
            throw $this->invalid($site, 'timezone must be a valid IANA identifier');
        }

        return [$currencyCode, $timezone];
    }

    private function invalid(Site $site, string $reason): InvalidSiteRuntimeConfigurationException
    {
        return InvalidSiteRuntimeConfigurationException::forSite((string) $site->code, $reason);
    }
}
