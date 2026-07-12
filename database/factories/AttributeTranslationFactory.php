<?php

namespace Database\Factories;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\Locale;
use App\Models\Translations\AttributeTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttributeTranslation> */
class AttributeTranslationFactory extends Factory
{
    protected $model = AttributeTranslation::class;

    public function definition(): array
    {
        return [
            'attribute_definition_id' => AttributeDefinition::factory(),
            'locale_id' => Locale::factory(),
            'locale' => fn (array $attributes): ?string => Locale::query()->find($attributes['locale_id'])?->code,
            'label' => fake()->words(2, true),
            'short_label' => fake()->optional()->word(),
            'help_text' => fake()->optional()->sentence(),
            'status' => TranslationStatus::HumanReviewed,
            'source_hash' => null,
        ];
    }
}
