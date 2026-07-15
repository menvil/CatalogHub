<?php

namespace App\View\Components\Public;

use App\Enums\PriceFreshnessStatus;
use App\Models\MarketOffer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;
use Illuminate\View\Component;

final class OfferTable extends Component
{
    /** @var Collection<int, MarketOffer> */
    public Collection $orderedOffers;

    /** @var array<int, string> */
    public array $formattedPrices = [];

    /**
     * @param  Collection<int, MarketOffer>  $offers
     * @param  array<int, PriceFreshnessStatus>  $freshness
     */
    public function __construct(
        public Collection $offers,
        public array $freshness = [],
        public ?MarketOffer $bestOffer = null,
        public string $locale = 'en',
    ) {
        $bestOfferId = $bestOffer?->getKey();
        $this->orderedOffers = $offers
            ->sort(function (MarketOffer $left, MarketOffer $right) use ($bestOfferId): int {
                $leftRank = $left->getKey() === $bestOfferId ? 0 : 1;
                $rightRank = $right->getKey() === $bestOfferId ? 0 : 1;

                return [$leftRank, (float) $left->price, (int) $left->getKey()]
                    <=> [$rightRank, (float) $right->price, (int) $right->getKey()];
            })
            ->values();

        foreach ($offers as $offer) {
            $this->formattedPrices[(int) $offer->getKey()] = Number::currency(
                (float) $offer->price,
                in: (string) $offer->getAttribute('currency'),
                locale: $locale,
            );
        }
    }

    public function render(): View
    {
        return view('components.public.offers.offer-table');
    }
}
