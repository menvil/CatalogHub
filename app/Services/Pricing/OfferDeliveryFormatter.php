<?php

namespace App\Services\Pricing;

use App\Models\MarketOffer;
use Illuminate\Support\Number;

final class OfferDeliveryFormatter
{
    public function price(MarketOffer $offer, string $locale = 'en'): string
    {
        if ($offer->delivery_price === null) {
            return 'Delivery price unknown';
        }

        if ((float) $offer->delivery_price === 0.0) {
            return 'Free delivery';
        }

        return 'Delivery: '.Number::currency(
            (float) $offer->delivery_price,
            in: (string) $offer->getAttribute('currency'),
            locale: $locale,
        );
    }

    public function time(MarketOffer $offer): string
    {
        $deliveryTime = trim((string) $offer->delivery_time);

        return $deliveryTime !== '' ? $deliveryTime : 'Delivery time unknown';
    }
}
