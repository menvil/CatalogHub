<?php

namespace Database\Factories;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SyncLog> */
class SyncLogFactory extends Factory
{
    protected $model = SyncLog::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'central_product_id' => CentralProduct::factory(),
            'central_category_id' => null,
            'operation' => fake()->randomElement(['apply_correction', 'rebuild_product_projection']),
            'status' => 'completed',
            'triggered_by' => 'user',
            'triggered_by_user_id' => User::factory()->centralAdmin(),
            'started_at' => now()->subSecond(),
            'finished_at' => now(),
            'affected_count' => 1,
            'error_message' => null,
            'context_json' => [],
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'completed',
            'finished_at' => now(),
            'error_message' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'failed',
            'finished_at' => now(),
            'error_message' => fake()->sentence(),
        ]);
    }
}
