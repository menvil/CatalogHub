<?php

namespace Database\Factories;

use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PriceSourceSyncLog> */
class PriceSourceSyncLogFactory extends Factory
{
    protected $model = PriceSourceSyncLog::class;

    public function definition(): array
    {
        return [
            'price_source_id' => PriceSource::factory(),
            'status' => 'queued',
            'started_at' => null,
            'finished_at' => null,
            'items_fetched' => 0,
            'items_normalized' => 0,
            'items_matched' => 0,
            'items_updated' => 0,
            'error_message' => null,
            'metadata' => [],
        ];
    }

    public function running(): static
    {
        return $this->state(fn (): array => ['status' => 'running', 'started_at' => now()]);
    }
}
