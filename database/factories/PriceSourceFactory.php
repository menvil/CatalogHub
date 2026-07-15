<?php

namespace Database\Factories;

use App\Enums\PriceSourceStatus;
use App\Enums\PriceSourceType;
use App\Models\Market;
use App\Models\PriceSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PriceSource> */
class PriceSourceFactory extends Factory
{
    protected $model = PriceSource::class;

    public function definition(): array
    {
        return [
            'market_id' => Market::factory(),
            'code' => 'source-'.fake()->unique()->uuid(),
            'name' => fake()->company().' Prices',
            'type' => PriceSourceType::Manual,
            'status' => PriceSourceStatus::Inactive,
            'config_json' => [],
            'update_frequency' => 'manual',
            'last_sync_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => PriceSourceStatus::Active]);
    }

    public function manual(): static
    {
        return $this->state(fn (): array => ['type' => PriceSourceType::Manual]);
    }
}
