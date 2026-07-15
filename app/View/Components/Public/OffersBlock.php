<?php

namespace App\View\Components\Public;

use App\Enums\PriceFreshnessStatus;
use App\Models\MarketOffer;
use App\Models\SiteProductProjection;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

final class OffersBlock extends Component
{
    /**
     * @param  Collection<int, MarketOffer>  $offers
     * @param  array<int, PriceFreshnessStatus>  $freshness
     */
    public function __construct(
        public SiteProductProjection $productProjection,
        public Collection $offers,
        public array $freshness = [],
        public ?MarketOffer $bestOffer = null,
        public string $locale = 'en',
    ) {}

    public function render(): View
    {
        return view('components.public.offers.offers-block');
    }
}
