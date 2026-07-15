<?php

namespace Database\Factories;

use App\Enums\ExternalProductMappingStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ExternalProductMapping;
use App\Models\PriceSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExternalProductMapping> */
class ExternalProductMappingFactory extends Factory
{
    protected $model = ExternalProductMapping::class;

    public function definition(): array
    {
        return [
            'price_source_id' => PriceSource::factory(),
            'central_product_id' => null,
            'external_product_id' => fake()->uuid(),
            'external_sku' => strtoupper(fake()->bothify('SKU-####-??')),
            'external_url' => fake()->url(),
            'external_title' => fake()->words(4, true),
            'confidence' => fake()->randomFloat(4, 0, 1),
            'status' => ExternalProductMappingStatus::Pending,
            'approved_at' => null,
            'approved_by_user_id' => null,
            'rejected_at' => null,
            'rejected_by_user_id' => null,
            'notes' => null,
            'metadata' => [],
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'central_product_id' => null,
            'status' => ExternalProductMappingStatus::Pending,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'central_product_id' => CentralProduct::factory(),
            'status' => ExternalProductMappingStatus::Approved,
            'approved_at' => now(),
            'approved_by_user_id' => User::factory(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => ExternalProductMappingStatus::Rejected,
            'rejected_at' => now(),
            'rejected_by_user_id' => User::factory(),
        ]);
    }
}
