<?php

namespace App\Services\Sites;

use App\Models\Site;
use App\Models\SiteOverride;

final class SiteOverrideResolver
{
    public function resolve(Site $site, string $entityType, int $entityId, string $field, ?string $localeCode, mixed $translatedCentralValue = null, mixed $fallbackValue = null): mixed
    {
        $query = SiteOverride::query()->where('site_id', $site->id)->where('entity_type', $entityType)->where('entity_id', $entityId)->where('field', $field)->where('status', 'active');
        $override = (clone $query)->where('locale_code', $localeCode)->first();
        $override ??= (clone $query)->whereNull('locale_code')->first();

        if ($override instanceof SiteOverride) {
            return $override->value();
        }

        if ($translatedCentralValue !== null && $translatedCentralValue !== '') {
            return $translatedCentralValue;
        }

        return $fallbackValue;
    }
}
