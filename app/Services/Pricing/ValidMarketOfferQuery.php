<?php

namespace App\Services\Pricing;

use App\Enums\MarketMerchantStatus;
use App\Enums\MarketOfferStatus;
use App\Models\MarketOffer;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;

final class ValidMarketOfferQuery
{
    public function __construct(private readonly SitePriceSourceSelection $sourceSelection) {}

    /** @return Builder<MarketOffer> */
    public function forSite(Site $site): Builder
    {
        $site->loadMissing('market');

        return MarketOffer::query()
            ->where('market_offers.market_id', $site->market_id)
            ->where('market_offers.currency', $site->market->currency_code)
            ->whereIn('market_offers.status', [MarketOfferStatus::Active, MarketOfferStatus::Stale])
            ->whereIn('market_offers.price_source_id', $this->sourceSelection
                ->enabledSources($site)
                ->select('price_sources.id'))
            ->whereHas('merchant', function (Builder $query) use ($site): void {
                $query
                    ->where('market_id', $site->market_id)
                    ->where('status', MarketMerchantStatus::Active);
            });
    }

    /** @return Builder<MarketOffer> */
    public function forProduct(Site $site, int $centralProductId): Builder
    {
        return $this->forSite($site)
            ->where('market_offers.central_product_id', $centralProductId);
    }

    public function findForSite(Site $site, MarketOffer|int $offer): MarketOffer
    {
        $offerId = $offer instanceof MarketOffer ? $offer->getKey() : $offer;

        return $this->forSite($site)->whereKey($offerId)->firstOrFail();
    }
}
