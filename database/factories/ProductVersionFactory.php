<?php

namespace Database\Factories;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVersion>
 */
class ProductVersionFactory extends Factory
{
    protected $model = ProductVersion::class;

    public function definition(): array
    {
        return [
            'central_product_id' => CentralProduct::factory(),
            'version' => 1,
            'changed_by_user_id' => User::factory(),
            'change_type' => 'manual_update',
            'reason' => fake()->sentence(),
            'snapshot_json' => ['name' => fake()->words(3, true)],
            'diff_json' => [],
            'metadata_json' => [],
        ];
    }
}
