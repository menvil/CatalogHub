<?php

namespace App\Services\Pricing;

use App\Data\Pricing\StalePriceWarningData;
use App\Enums\MarketMerchantStatus;
use App\Enums\MarketOfferStatus;
use App\Enums\PriceFreshnessStatus;
use App\Enums\PriceSourceSyncStatus;
use App\Models\MarketOffer;
use App\Models\PriceSourceSyncLog;
use App\Models\Site;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final readonly class StalePriceWarningBuilder
{
    public function __construct(
        private SitePriceSourceSelection $sourceSelection,
        private PriceFreshnessCalculator $freshness,
    ) {}

    public function build(Site $site): StalePriceWarningData
    {
        $sourceIds = $this->sourceSelection->enabledSources($site)->select('price_sources.id');
        $offers = MarketOffer::query()
            ->where('market_offers.market_id', $site->market_id)
            ->where('market_offers.currency', $site->market->currency_code)
            ->whereIn('market_offers.price_source_id', $sourceIds)
            ->whereIn('market_offers.status', [
                MarketOfferStatus::Active,
                MarketOfferStatus::Stale,
                MarketOfferStatus::Expired,
            ])
            ->whereHas('merchant', fn (Builder $merchant): Builder => $merchant
                ->where('status', MarketMerchantStatus::Active))
            ->with('priceSource')
            ->lazy();
        $stale = 0;
        $expired = 0;
        $affectedProducts = [];

        foreach ($offers as $offer) {
            $status = $offer->status === MarketOfferStatus::Expired
                ? PriceFreshnessStatus::Expired
                : $this->freshness->calculate($offer, site: $site);

            if ($status === PriceFreshnessStatus::Stale) {
                $stale++;
            } elseif ($status === PriceFreshnessStatus::Expired) {
                $expired++;
            } else {
                continue;
            }

            $affectedProducts[(int) $offer->central_product_id] = true;
        }

        $lastSuccessfulUpdate = PriceSourceSyncLog::query()
            ->whereIn('price_source_id', $this->sourceSelection->enabledSources($site)->select('price_sources.id'))
            ->where('status', PriceSourceSyncStatus::Completed)
            ->whereNotNull('finished_at')
            ->latest('finished_at')
            ->first()?->getAttribute('finished_at');

        return new StalePriceWarningData(
            staleOffersCount: $stale,
            expiredOffersCount: $expired,
            affectedProductsCount: count($affectedProducts),
            lastSuccessfulUpdateAt: $lastSuccessfulUpdate instanceof CarbonInterface
                ? $lastSuccessfulUpdate
                : null,
        );
    }
}
