<?php

namespace App\Listeners;

use App\Enums\PriceSourceStatus;
use App\Events\MarketOfferUpdated;
use App\Jobs\Projections\RebuildPriceAffectedProjectionJob;
use App\Models\MarketOffer;
use App\Models\Site;
use App\Services\Pricing\SitePriceSourceSelection;
use Illuminate\Database\Eloquent\Builder;

final readonly class RebuildPriceAffectedProjections
{
    public function __construct(private SitePriceSourceSelection $sourceSelection) {}

    public function handle(MarketOfferUpdated $event): void
    {
        $offer = MarketOffer::query()->with('priceSource')->find($event->marketOfferId);

        if (! $offer instanceof MarketOffer || $offer->priceSource->status !== PriceSourceStatus::Active) {
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
                foreach ($sites as $site) {
                    if (! $this->sourceSelection->enabledSources($site)->whereKey($offer->price_source_id)->exists()) {
                        continue;
                    }

                    RebuildPriceAffectedProjectionJob::dispatch(
                        (int) $site->getKey(),
                        (int) $offer->central_product_id,
                    )->afterCommit();
                }
            });
    }
}
