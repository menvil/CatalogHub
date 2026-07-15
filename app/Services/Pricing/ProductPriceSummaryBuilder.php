<?php

namespace App\Services\Pricing;

use App\Data\Pricing\ProductPriceSummary;
use App\Enums\OfferAvailability;
use App\Models\MarketOffer;
use App\Models\Site;
use Carbon\CarbonImmutable;

final class ProductPriceSummaryBuilder
{
    public function __construct(
        private readonly ValidMarketOfferQuery $validOffers,
        private readonly SitePriceSourceConfigResolver $sourceConfig,
    ) {}

    public function build(int $siteId, int $centralProductId): ProductPriceSummary
    {
        $site = Site::query()->with('market')->findOrFail($siteId);
        $offers = $this->sourceConfig->applySummaryPolicy(
            $this->validOffers->forProduct($site, $centralProductId),
            $site,
        )->get();
        $minimum = $offers->min('price');
        $maximum = $offers->max('price');
        $lastPriceUpdateAt = $offers->max('last_checked_at');

        return new ProductPriceSummary(
            minPrice: $this->money($minimum),
            maxPrice: $this->money($maximum),
            offersCount: $offers->count(),
            inStock: $offers->contains(
                fn (MarketOffer $offer): bool => $offer->availability === OfferAvailability::InStock,
            ),
            lastPriceUpdateAt: $lastPriceUpdateAt === null
                ? null
                : CarbonImmutable::parse((string) $lastPriceUpdateAt),
        );
    }

    private function money(mixed $value): ?string
    {
        return $value === null ? null : number_format((float) $value, 2, '.', '');
    }
}
