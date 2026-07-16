<?php

namespace Database\Factories;

use App\Enums\SyncConflictStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SyncConflict;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SyncConflict> */
class SyncConflictFactory extends Factory
{
    protected $model = SyncConflict::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'central_product_id' => CentralProduct::factory(),
            'entity_type' => 'central_product',
            'entity_id' => null,
            'field_path' => 'name',
            'central_value_json' => ['value' => fake()->words(3, true)],
            'local_value_json' => ['value' => fake()->words(3, true)],
            'conflict_type' => 'local_override',
            'status' => SyncConflictStatus::Open,
            'resolution' => null,
            'resolved_by_user_id' => null,
            'resolved_at' => null,
            'metadata_json' => [],
        ];
    }

    public function open(): static
    {
        return $this->state(fn (): array => [
            'status' => SyncConflictStatus::Open,
            'resolution' => null,
            'resolved_by_user_id' => null,
            'resolved_at' => null,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'status' => SyncConflictStatus::Resolved,
            'resolution' => 'use_central_value',
            'resolved_by_user_id' => User::factory()->centralAdmin(),
            'resolved_at' => now(),
        ]);
    }

    public function ignored(): static
    {
        return $this->state(fn (): array => [
            'status' => SyncConflictStatus::Ignored,
            'resolution' => 'ignored',
            'resolved_by_user_id' => User::factory()->centralAdmin(),
            'resolved_at' => now(),
        ]);
    }
}
