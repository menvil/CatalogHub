<?php

declare(strict_types=1);

namespace App\Support\Sites;

use App\Models\Market;
use App\Models\Site;
use App\Models\SiteDomain;
use InvalidArgumentException;

final readonly class SiteRuntimeContext
{
    public function __construct(
        public Site $site,
        public SiteDomain $domain,
        public Market $market,
        public ?string $requestedLocale,
        public string $resolvedLocale,
        public string $currencyCode,
        public string $timezone,
    ) {
        if ($site->getKey() === null || $domain->getKey() === null || $market->getKey() === null) {
            throw new InvalidArgumentException('Persisted site, domain, and market values are required.');
        }

        if ((int) $domain->site_id !== (int) $site->getKey()
            || (int) $site->market_id !== (int) $market->getKey()) {
            throw new InvalidArgumentException('Site runtime context relations are inconsistent.');
        }

        if ($resolvedLocale === '' || $currencyCode === '' || $timezone === '') {
            throw new InvalidArgumentException('Locale, currency, and timezone values are required.');
        }
    }

    /** @return array<string, int|string|null> */
    public function __debugInfo(): array
    {
        return [
            'site_id' => (int) $this->site->getKey(),
            'site_code' => (string) $this->site->code,
            'host' => (string) $this->domain->host,
            'domain_type' => $this->domain->type->value,
            'market_code' => (string) $this->market->code,
            'requested_locale' => $this->requestedLocale,
            'resolved_locale' => $this->resolvedLocale,
            'currency_code' => $this->currencyCode,
            'timezone' => $this->timezone,
        ];
    }
}
