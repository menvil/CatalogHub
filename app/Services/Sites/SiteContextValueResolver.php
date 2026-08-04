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
        $market = $site->market;

        if (! $market instanceof Market) {
            throw $this->invalid($site, 'market is missing');
        }

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
        $resolvedLocale = is_string($requestedLocale) && in_array($requestedLocale, $enabledLocales, true)
            ? $requestedLocale
            : $defaultLocale->locale_code;
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

    private function invalid(Site $site, string $reason): InvalidSiteRuntimeConfigurationException
    {
        return InvalidSiteRuntimeConfigurationException::forSite((string) $site->code, $reason);
    }
}
