<?php

namespace App\Services\Pricing;

use App\Data\Pricing\ProductPriceSummary;
use App\Enums\MarketOfferStatus;
use App\Enums\OfferAvailability;
use App\Enums\PriceSourceStatus;
use App\Models\MarketOffer;
use App\Models\Site;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class ProductPriceSummaryBuilder
{
    public function build(int $siteId, int $centralProductId): ProductPriceSummary
    {
        $site = Site::query()->with('market')->findOrFail($siteId);
        $offers = $this->validOffers($site, $centralProductId);
        $minimum = (clone $offers)->min('price');
        $maximum = (clone $offers)->max('price');
        $lastPriceUpdateAt = (clone $offers)->max('last_checked_at');

        return new ProductPriceSummary(
            minPrice: $this->money($minimum),
            maxPrice: $this->money($maximum),
            offersCount: (clone $offers)->count(),
            inStock: (clone $offers)->where('availability', OfferAvailability::InStock)->exists(),
            lastPriceUpdateAt: $lastPriceUpdateAt === null
                ? null
                : CarbonImmutable::parse((string) $lastPriceUpdateAt),
        );
    }

    /** @return Builder<MarketOffer> */
    private function validOffers(Site $site, int $centralProductId): Builder
    {
        return MarketOffer::query()
            ->where('market_offers.market_id', $site->market_id)
            ->where('market_offers.central_product_id', $centralProductId)
            ->where('market_offers.currency', $site->market->currency_code)
            ->where('market_offers.status', MarketOfferStatus::Active)
            ->whereHas('priceSource', function (Builder $query) use ($site): void {
                $query
                    ->where('market_id', $site->market_id)
                    ->where('status', PriceSourceStatus::Active);
            });
    }

    private function money(mixed $value): ?string
    {
        return $value === null ? null : number_format((float) $value, 2, '.', '');
    }
}
