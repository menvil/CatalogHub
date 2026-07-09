<?php

namespace Database\Factories;

use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CentralBrand>
 */
class CentralBrandFactory extends Factory
{
    protected $model = CentralBrand::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => CentralBrandStatus::default(),
        ];
    }
}
