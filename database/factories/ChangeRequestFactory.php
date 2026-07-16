<?php

namespace Database\Factories;

use App\Enums\ChangeRequestStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ChangeRequest;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ChangeRequest> */
class ChangeRequestFactory extends Factory
{
    protected $model = ChangeRequest::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'central_product_id' => CentralProduct::factory(),
            'entity_type' => 'central_product',
            'entity_id' => null,
            'field_path' => 'name',
            'old_value_json' => ['value' => fake()->words(3, true)],
            'proposed_value_json' => ['value' => fake()->words(3, true)],
            'evidence_url' => fake()->url(),
            'evidence_note' => fake()->sentence(),
            'status' => ChangeRequestStatus::Pending,
            'created_by_user_id' => User::factory(),
            'reviewed_by_user_id' => null,
            'applied_by_user_id' => null,
            'reviewed_at' => null,
            'applied_at' => null,
            'rejection_reason' => null,
            'metadata_json' => [],
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => ChangeRequestStatus::Pending,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => ChangeRequestStatus::Approved,
            'reviewed_by_user_id' => User::factory()->centralAdmin(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => ChangeRequestStatus::Rejected,
            'reviewed_by_user_id' => User::factory()->centralAdmin(),
            'reviewed_at' => now(),
            'rejection_reason' => 'Evidence is not reliable.',
        ]);
    }

    public function applied(): static
    {
        return $this->approved()->state(fn (): array => [
            'status' => ChangeRequestStatus::Applied,
            'applied_by_user_id' => User::factory()->centralAdmin(),
            'applied_at' => now(),
        ]);
    }
}
