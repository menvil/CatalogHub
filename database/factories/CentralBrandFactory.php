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
            'website_url' => null,
            'country_code' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => CentralBrandStatus::Draft,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => CentralBrandStatus::Active,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => CentralBrandStatus::Archived,
        ]);
    }
}
