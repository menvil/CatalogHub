<?php

namespace Database\Factories;

use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Support\Normalization\BrandInputNormalizer;
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
            'normalized_name' => fn (array $attributes): string => BrandInputNormalizer::nameIdentity((string) $attributes['name']),
            'normalized_name_hash' => fn (array $attributes): string => BrandInputNormalizer::nameIdentityHash((string) $attributes['name']),
            'slug' => $slug,
            'status' => CentralBrandStatus::default(),
            'website_url' => null,
            'country_id' => null,
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
