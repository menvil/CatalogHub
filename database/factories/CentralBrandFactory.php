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
        $slug = Str::slug($name).'-'.fake()->unique()->numerify('####');

        return [
            'name' => $name,
            'slug' => $slug,
            'status' => CentralBrandStatus::default(),
        ];
    }
}
