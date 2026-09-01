<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Support\Normalization\OrganizationNameNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Organization> */
final class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'normalized_name' => fn (array $attributes): string => OrganizationNameNormalizer::search((string) $attributes['name']),
            'normalized_name_prefix' => fn (array $attributes): string => OrganizationNameNormalizer::prefixForNormalizedName(
                OrganizationNameNormalizer::search((string) $attributes['name']),
            ),
        ];
    }
}
