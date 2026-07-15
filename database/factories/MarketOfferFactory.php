<?php

namespace Database\Factories;

use App\Enums\MarketOfferStatus;
use App\Enums\OfferAvailability;
use App\Enums\OfferCondition;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Market;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MarketOffer> */
class MarketOfferFactory extends Factory
{
    protected $model = MarketOffer::class;

    public function definition(): array
    {
        return [
            'market_id' => Market::factory(),
            'market_merchant_id' => MarketMerchant::factory(),
            'central_product_id' => CentralProduct::factory(),
            'price_source_id' => PriceSource::factory(),
            'external_product_mapping_id' => null,
            'price' => fake()->randomFloat(2, 1, 5000),
            'currency' => 'EUR',
            'original_price' => null,
            'original_currency' => null,
            'availability' => OfferAvailability::InStock,
            'condition' => OfferCondition::New,
            'delivery_price' => null,
            'delivery_time' => null,
            'url' => fake()->url(),
            'last_seen_at' => now(),
            'last_checked_at' => now(),
            'status' => MarketOfferStatus::Active,
            'metadata' => [],
        ];
    }
}
