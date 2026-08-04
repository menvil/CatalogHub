<?php

declare(strict_types=1);

namespace App\Services\Sites;

use App\Enums\SiteDomainType;
use App\Enums\SiteResolutionMode;
use App\Enums\SiteStatus;
use App\Exceptions\Sites\UnknownSiteException;
use App\Models\Site;
use App\Models\SiteDomain;
use Illuminate\Database\Eloquent\Builder;

final class SiteResolver
{
    public function resolve(
        string $host,
        SiteResolutionMode $mode = SiteResolutionMode::Public,
    ): Site {
        return $this->resolveDomain($host, $mode)->site;
    }

    public function resolveDomain(
        string $host,
        SiteResolutionMode $mode = SiteResolutionMode::Public,
    ): SiteDomain {
        $normalizedHost = SiteDomain::normalizeHost($host);
        $allowedStatuses = array_values(array_map(
            static fn (SiteStatus $status): string => $status->value,
            array_filter(
                SiteStatus::cases(),
                static fn (SiteStatus $status): bool => $mode === SiteResolutionMode::Public
                    ? $status->isPubliclyAvailable()
                    : $status->allowsAdministration(),
            ),
        ));

        $query = SiteDomain::query()
            ->with(['site.market', 'site.locales.locale', 'site.domains'])
            ->where('host', $normalizedHost)
            ->where('is_active', true)
            ->whereHas('site', function (Builder $site) use ($allowedStatuses): void {
                $site->whereIn('status', $allowedStatuses);
            });

        if ($mode === SiteResolutionMode::Public) {
            $query->whereIn('type', [
                SiteDomainType::Primary,
                SiteDomainType::Alias,
            ]);
        }

        $domain = $query->first();

        if (! $domain instanceof SiteDomain) {
            throw UnknownSiteException::forHost($normalizedHost);
        }

        return $domain;
    }
}
