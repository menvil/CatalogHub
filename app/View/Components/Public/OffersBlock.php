<?php

namespace App\View\Components\Public;

use App\Data\Pricing\ExternalPriceWidgetData;
use App\Enums\PriceFreshnessStatus;
use App\Models\MarketOffer;
use App\Models\Site;
use App\Models\SiteProductProjection;
use App\Services\Pricing\ExternalWidgetRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

final class OffersBlock extends Component
{
    public ?ExternalPriceWidgetData $externalWidget;

    /**
     * @param  Collection<int, MarketOffer>  $offers
     * @param  array<int, PriceFreshnessStatus>  $freshness
     */
    public function __construct(
        ExternalWidgetRenderer $widgets,
        public Site $site,
        public SiteProductProjection $productProjection,
        public Collection $offers,
        public array $freshness = [],
        public ?MarketOffer $bestOffer = null,
        public string $locale = 'en',
    ) {
        $this->externalWidget = $widgets->resolve($site, $productProjection, $offers->isNotEmpty());
    }

    public function render(): View
    {
        return view('components.public.offers.offers-block');
    }
}
