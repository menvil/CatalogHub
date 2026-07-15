<?php

namespace App\View\Components\Public;

use App\Models\MarketOffer;
use App\Models\SiteProductProjection;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;
use Illuminate\View\Component;

final class OffersBlock extends Component
{
    /** @var array<int, string> */
    public array $formattedPrices = [];

    /** @param Collection<int, MarketOffer> $offers */
    public function __construct(
        public SiteProductProjection $productProjection,
        public Collection $offers,
        public ?MarketOffer $bestOffer = null,
        public string $locale = 'en',
    ) {
        foreach ($offers as $offer) {
            $this->formattedPrices[(int) $offer->getKey()] = $this->formatPrice($offer);
        }

        if ($bestOffer !== null && ! isset($this->formattedPrices[(int) $bestOffer->getKey()])) {
            $this->formattedPrices[(int) $bestOffer->getKey()] = $this->formatPrice($bestOffer);
        }
    }

    private function formatPrice(MarketOffer $offer): string
    {
        return Number::currency(
            (float) $offer->price,
            in: (string) $offer->getAttribute('currency'),
            locale: $this->locale,
        );
    }

    public function render(): View
    {
        return view('components.public.offers.offers-block');
    }
}
