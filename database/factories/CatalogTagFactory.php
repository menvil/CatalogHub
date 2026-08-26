<?php

namespace Database\Factories;

use App\Models\CentralCatalog\CatalogTag;
use App\Support\Normalization\CatalogTagNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CatalogTag> */
final class CatalogTagFactory extends Factory
{
    protected $model = CatalogTag::class;

    public function definition(): array
    {
        $name = 'Catalog Tag '.fake()->unique()->numberBetween(1, 999999);

        return [
            'name' => $name,
            'normalized_name' => fn (array $attributes): string => CatalogTagNormalizer::identity(CatalogTagNormalizer::name((string) $attributes['name'])),
            'normalized_name_hash' => fn (array $attributes): string => CatalogTagNormalizer::identityHash(CatalogTagNormalizer::name((string) $attributes['name'])),
        ];
    }
}
