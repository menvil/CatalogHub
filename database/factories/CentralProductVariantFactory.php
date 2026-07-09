<?php

namespace Database\Factories;

use App\Enums\CentralProductVariantStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CentralProductVariant>
 */
class CentralProductVariantFactory extends Factory
{
    protected $model = CentralProductVariant::class;

    public function definition(): array
    {
        return [
            'central_product_id' => CentralProduct::factory(),
            'name' => fake()->optional()->words(2, true),
            'sku' => fake()->optional()->unique()->bothify('SKU-####-??'),
            'status' => CentralProductVariantStatus::default(),
            'position' => 0,
        ];
    }
}
