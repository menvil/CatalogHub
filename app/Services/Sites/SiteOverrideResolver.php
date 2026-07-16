<?php

namespace App\Services\Sites;

use App\Models\Site;
use App\Models\SiteOverride;
use Illuminate\Database\Eloquent\Builder;

final class SiteOverrideResolver
{
    public function resolve(Site $site, string $entityType, int $entityId, string $field, ?string $localeCode, mixed $translatedCentralValue = null, mixed $fallbackValue = null): mixed
    {
        $requestedLocale = $localeCode ?? '';
        $scope = SiteOverride::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('field', $field)
            ->where('status', 'active');
        $override = $this->forLocale(
            (clone $scope)->where('site_id', $site->id),
            $requestedLocale,
        );

        if (! $override instanceof SiteOverride) {
            $override = $this->forLocale(
                (clone $scope)
                    ->whereNull('site_id')
                    ->where('market_id', $site->market_id),
                $requestedLocale,
            );
        }

        if ($override instanceof SiteOverride) {
            return $override->overrideValue();
        }

        if ($translatedCentralValue !== null && $translatedCentralValue !== '') {
            return $translatedCentralValue;
        }

        return $fallbackValue;
    }

    /** @param Builder<SiteOverride> $query */
    private function forLocale(Builder $query, string $requestedLocale): ?SiteOverride
    {
        $override = (clone $query)->where('locale_code', $requestedLocale)->first();

        if (! $override instanceof SiteOverride && $requestedLocale !== '') {
            $override = (clone $query)->where('locale_code', '')->first();
        }

        return $override;
    }
}
