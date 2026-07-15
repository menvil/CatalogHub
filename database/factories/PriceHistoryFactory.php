<?php

namespace Database\Factories;

use App\Enums\OfferAvailability;
use App\Enums\OfferCondition;
use App\Models\MarketOffer;
use App\Models\PriceHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PriceHistory> */
class PriceHistoryFactory extends Factory
{
    protected $model = PriceHistory::class;

    public function definition(): array
    {
        return [
            'market_offer_id' => MarketOffer::factory(),
            'price' => fake()->randomFloat(2, 1, 5000),
            'currency' => 'EUR',
            'availability' => OfferAvailability::InStock,
            'condition' => OfferCondition::New,
            'delivery_price' => null,
            'checked_at' => now(),
            'source_snapshot_json' => [],
        ];
    }
}
