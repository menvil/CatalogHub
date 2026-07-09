<?php

namespace Database\Factories;

use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralProduct;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CentralProduct>
 */
class CentralProductFactory extends Factory
{
    protected $model = CentralProduct::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $model = strtoupper(fake()->bothify('??-####'));

        return [
            'name' => str($name)->headline()->toString(),
            'model' => $model,
            'slug' => Str::slug($name.' '.$model),
            'status' => CentralProductStatus::default(),
        ];
    }
}
