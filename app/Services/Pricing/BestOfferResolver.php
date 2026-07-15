<?php

namespace App\Services\Pricing;

use App\Enums\OfferAvailability;
use App\Enums\PriceFreshnessStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MarketOffer;
use App\Models\Site;

final readonly class BestOfferResolver
{
    public function __construct(
        private ValidMarketOfferQuery $validOffers,
        private PriceFreshnessCalculator $freshness,
    ) {}

    public function resolve(Site $site, CentralProduct|int $product): ?MarketOffer
    {
        $productId = $product instanceof CentralProduct ? (int) $product->getKey() : $product;
        $candidate = $this->validOffers->forProduct($site, $productId)
            ->where('availability', OfferAvailability::InStock)
            ->with(['merchant.logoMediaAsset', 'priceSource'])
            ->get()
            ->filter(fn (MarketOffer $offer): bool => $this->freshness->calculate($offer, site: $site) !== PriceFreshnessStatus::Expired)
            ->sort(function (MarketOffer $left, MarketOffer $right) use ($site): int {
                $totalComparison = $this->totalCents($left) <=> $this->totalCents($right);

                if ($totalComparison !== 0) {
                    return $totalComparison;
                }

                $freshnessComparison = $this->freshnessRank($left, $site) <=> $this->freshnessRank($right, $site);

                return $freshnessComparison !== 0
                    ? $freshnessComparison
                    : (int) $left->getKey() <=> (int) $right->getKey();
            })
            ->first();

        return $candidate instanceof MarketOffer ? $candidate : null;
    }

    private function totalCents(MarketOffer $offer): int
    {
        return $this->cents($offer->price)
            + ($offer->delivery_price === null ? 0 : $this->cents($offer->delivery_price));
    }

    private function cents(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function freshnessRank(MarketOffer $offer, Site $site): int
    {
        return match ($this->freshness->calculate($offer, site: $site)) {
            PriceFreshnessStatus::Fresh => 0,
            PriceFreshnessStatus::Stale => 1,
            PriceFreshnessStatus::Unknown => 2,
            PriceFreshnessStatus::Expired => 3,
        };
    }
}
