<?php

namespace Database\Factories;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\Translations\ProductTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductTranslation> */
class ProductTranslationFactory extends Factory
{
    protected $model = ProductTranslation::class;

    public function definition(): array
    {
        return [
            'product_id' => CentralProduct::factory(),
            'locale_id' => Locale::factory(),
            'locale' => 'de-DE',
            'name' => fake()->words(4, true),
            'subtitle' => fake()->optional()->sentence(3),
            'short_description' => fake()->optional()->sentence(),
            'description' => fake()->optional()->paragraph(),
            'seo_title' => fake()->optional()->sentence(3),
            'seo_description' => fake()->optional()->sentence(),
            'status' => TranslationStatus::HumanReviewed,
            'source_hash' => null,
        ];
    }
}
