<?php

namespace App\Services\Pricing;

use App\Enums\OfferAvailability;
use App\Enums\PriceFreshnessStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MarketOffer;
use App\Models\Site;
use App\Queries\Pricing\ValidMarketOfferQuery;
use Illuminate\Support\Collection;

final readonly class BestOfferResolver
{
    public function __construct(
        private ValidMarketOfferQuery $validOffers,
        private PriceFreshnessCalculator $freshness,
    ) {}

    public function resolve(Site $site, CentralProduct|int $product): ?MarketOffer
    {
        $productId = $product instanceof CentralProduct ? (int) $product->getKey() : $product;
        $offers = $this->validOffers->forProduct($site, $productId)
            ->where('availability', OfferAvailability::InStock)
            ->with(['merchant.logoMediaAsset', 'priceSource'])
            ->get();

        return $this->resolveFromOffers($site, $offers);
    }

    /**
     * @param  Collection<int, MarketOffer>  $offers
     * @param  array<int, PriceFreshnessStatus>  $statuses
     */
    public function resolveFromOffers(Site $site, Collection $offers, array $statuses = []): ?MarketOffer
    {
        $candidate = $offers
            ->filter(function (MarketOffer $offer) use ($site, &$statuses): bool {
                if ($offer->availability !== OfferAvailability::InStock) {
                    return false;
                }

                $offerId = (int) $offer->getKey();
                $status = $statuses[$offerId]
                    ?? $this->freshness->calculate($offer, site: $site);
                $statuses[$offerId] = $status;

                return $status !== PriceFreshnessStatus::Expired;
            })
            ->sort(function (MarketOffer $left, MarketOffer $right) use ($statuses): int {
                $totalComparison = $this->totalCents($left) <=> $this->totalCents($right);

                if ($totalComparison !== 0) {
                    return $totalComparison;
                }

                $freshnessComparison = $this->freshnessRank(
                    $statuses[(int) $left->getKey()] ?? PriceFreshnessStatus::Unknown,
                ) <=> $this->freshnessRank(
                    $statuses[(int) $right->getKey()] ?? PriceFreshnessStatus::Unknown,
                );

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

    private function freshnessRank(PriceFreshnessStatus $status): int
    {
        return match ($status) {
            PriceFreshnessStatus::Fresh => 0,
            PriceFreshnessStatus::Stale => 1,
            PriceFreshnessStatus::Unknown => 2,
            PriceFreshnessStatus::Expired => 3,
        };
    }
}
