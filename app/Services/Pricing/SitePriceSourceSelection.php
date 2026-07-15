<?php

namespace App\Services\Pricing;

use App\Enums\PriceSourceStatus;
use App\Models\PriceSource;
use App\Models\Site;
use App\Models\SitePriceSource;
use Illuminate\Database\Eloquent\Builder;

final class SitePriceSourceSelection
{
    public function hasExplicitSelection(Site $site): bool
    {
        return SitePriceSource::query()->where('site_id', $site->id)->exists();
    }

    /** @return Builder<PriceSource> */
    public function enabledSources(Site $site): Builder
    {
        $query = PriceSource::query()
            ->where('price_sources.market_id', $site->market_id)
            ->where('price_sources.status', PriceSourceStatus::Active);

        if ($this->hasExplicitSelection($site)) {
            $query->whereIn('price_sources.id', SitePriceSource::query()
                ->select('price_source_id')
                ->where('site_id', $site->id)
                ->where('enabled', true));
        }

        return $query;
    }

    /** @param list<int> $selectedSourceIds */
    public function update(Site $site, array $selectedSourceIds): void
    {
        $marketSourceIds = PriceSource::query()
            ->where('market_id', $site->market_id)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
        $selected = array_fill_keys($selectedSourceIds, true);
        $pivotValues = [];

        foreach ($marketSourceIds as $sourceId) {
            $pivotValues[$sourceId] = ['enabled' => isset($selected[$sourceId])];
        }

        $site->priceSources()->sync($pivotValues);
    }
}
