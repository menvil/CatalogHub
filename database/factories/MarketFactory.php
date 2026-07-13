<?php

namespace Database\Factories;

use App\Enums\MarketStatus;
use App\Models\Market;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Market>
 */
class MarketFactory extends Factory
{
    protected $model = Market::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('??#####')),
            'name' => fake()->country(),
            'country_code' => strtoupper(fake()->countryCode()),
            'currency_code' => 'EUR',
            'default_locale' => 'en-US',
            'timezone' => 'UTC',
            'status' => MarketStatus::default(),
            'config_json' => [],
        ];
    }
}
