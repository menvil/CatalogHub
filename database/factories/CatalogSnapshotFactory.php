<?php

namespace Database\Factories;

use App\Models\CatalogSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CatalogSnapshot> */
class CatalogSnapshotFactory extends Factory
{
    protected $model = CatalogSnapshot::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'status' => 'pending',
            'snapshot_type' => 'full',
            'storage_disk' => 'local',
            'storage_path' => null,
            'files_json' => [],
            'metadata_json' => [],
            'started_at' => null,
            'completed_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
            'created_by_user_id' => User::factory()->centralAdmin(),
        ];
    }

    public function generating(): static
    {
        return $this->state(fn (): array => [
            'status' => 'generating',
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'completed',
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'failed',
            'started_at' => now()->subMinute(),
            'failed_at' => now(),
            'failure_reason' => fake()->sentence(),
        ]);
    }
}
