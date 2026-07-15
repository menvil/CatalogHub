<?php

namespace Database\Factories;

use App\Enums\MarketMerchantStatus;
use App\Models\Market;
use App\Models\MarketMerchant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<MarketMerchant> */
class MarketMerchantFactory extends Factory
{
    protected $model = MarketMerchant::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'market_id' => Market::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('######'),
            'website_url' => fake()->url(),
            'logo_media_asset_id' => null,
            'status' => MarketMerchantStatus::Active,
            'metadata' => [],
        ];
    }
}
