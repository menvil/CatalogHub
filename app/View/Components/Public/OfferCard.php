<?php

namespace App\View\Components\Public;

use App\Enums\PriceFreshnessStatus;
use App\Models\MarketOffer;
use App\Models\MediaAsset;
use App\Services\Media\MediaUrlGenerator;
use App\Services\Pricing\OfferDeliveryFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Number;
use Illuminate\View\Component;

final class OfferCard extends Component
{
    public PriceFreshnessStatus $freshness;

    public string $formattedPrice;

    public ?string $merchantLogoUrl;

    public string $merchantInitial;

    public string $deliverySummary;

    public function __construct(
        MediaUrlGenerator $mediaUrls,
        OfferDeliveryFormatter $deliveryFormatter,
        public MarketOffer $offer,
        PriceFreshnessStatus|string $freshness = PriceFreshnessStatus::Unknown,
        public ?string $actionUrl = null,
        public string $locale = 'en',
    ) {
        $this->freshness = is_string($freshness)
            ? PriceFreshnessStatus::tryFrom($freshness) ?? PriceFreshnessStatus::Unknown
            : $freshness;
        $this->formattedPrice = Number::currency(
            (float) $offer->price,
            in: (string) $offer->getAttribute('currency'),
            locale: $locale,
        );
        $logo = $offer->merchant->logoMediaAsset;
        $this->merchantLogoUrl = $logo instanceof MediaAsset ? $mediaUrls->forAsset($logo) : null;
        $merchantName = trim($offer->merchant->name);
        $this->merchantInitial = mb_strtoupper(mb_substr($merchantName, 0, 1));
        $this->deliverySummary = $deliveryFormatter->price($offer, $locale);
    }

    public function render(): View
    {
        return view('components.public.offers.offer-card');
    }
}
