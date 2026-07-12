<?php

namespace Database\Factories;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\Translations\CategoryTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CategoryTranslation> */
class CategoryTranslationFactory extends Factory
{
    protected $model = CategoryTranslation::class;

    public function definition(): array
    {
        return [
            'category_id' => CentralCategory::factory(),
            'locale_id' => Locale::factory(),
            'locale' => fn (array $attributes): ?string => Locale::query()->find($attributes['locale_id'])?->code,
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'seo_title' => fake()->optional()->sentence(3),
            'seo_description' => fake()->optional()->sentence(),
            'status' => TranslationStatus::HumanReviewed,
            'source_hash' => null,
        ];
    }
}
