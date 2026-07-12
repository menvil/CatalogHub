<?php

namespace Database\Factories;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\Locale;
use App\Models\Translations\AttributeOptionTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttributeOptionTranslation> */
class AttributeOptionTranslationFactory extends Factory
{
    protected $model = AttributeOptionTranslation::class;

    public function definition(): array
    {
        return [
            'attribute_option_id' => AttributeOption::factory(),
            'locale_id' => Locale::factory(),
            'locale' => fn (array $attributes): ?string => Locale::query()->find($attributes['locale_id'])?->code,
            'label' => fake()->word(),
            'description' => fake()->optional()->sentence(),
            'status' => TranslationStatus::HumanReviewed,
            'source_hash' => null,
        ];
    }
}
