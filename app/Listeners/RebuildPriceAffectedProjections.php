<?php

namespace App\Listeners;

use App\Enums\PriceSourceStatus;
use App\Events\MarketOfferUpdated;
use App\Jobs\Projections\RebuildPriceAffectedProjectionJob;
use App\Models\MarketOffer;
use App\Models\Site;
use App\Models\SitePriceSource;
use Illuminate\Database\Eloquent\Builder;

final readonly class RebuildPriceAffectedProjections
{
    public function handle(MarketOfferUpdated $event): void
    {
        $offer = MarketOffer::query()->with('priceSource')->find($event->marketOfferId);

        if (! $offer instanceof MarketOffer || $offer->priceSource?->status !== PriceSourceStatus::Active) {
            return;
        }

        Site::query()
            ->where('market_id', $offer->market_id)
            ->whereHas('products', function (Builder $products) use ($offer): void {
                $products
                    ->where('central_product_id', $offer->central_product_id)
                    ->where('visibility', 'visible');
            })
            ->chunkById(100, function ($sites) use ($offer): void {
                /** @var array<int, array<int, bool>> $selectionsBySite */
                $selectionsBySite = [];

                foreach (SitePriceSource::query()
                    ->whereIn('site_id', $sites->modelKeys())
                    ->get(['site_id', 'price_source_id', 'enabled']) as $selection) {
                    $selectionsBySite[(int) $selection->getAttribute('site_id')][
                        (int) $selection->getAttribute('price_source_id')
                    ] = $selection->enabled;
                }

                foreach ($sites as $site) {
                    $siteId = (int) $site->getKey();
                    $sourceId = (int) $offer->price_source_id;

                    if (isset($selectionsBySite[$siteId]) && ! ($selectionsBySite[$siteId][$sourceId] ?? false)) {
                        continue;
                    }

                    RebuildPriceAffectedProjectionJob::dispatch(
                        $siteId,
                        (int) $offer->central_product_id,
                    )->afterCommit();
                }
            });
    }
}
