<?php

namespace Database\Factories;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\Locale;
use App\Models\Translations\AttributeSectionTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttributeSectionTranslation> */
class AttributeSectionTranslationFactory extends Factory
{
    protected $model = AttributeSectionTranslation::class;

    public function definition(): array
    {
        return [
            'attribute_section_id' => AttributeSection::factory(),
            'locale_id' => Locale::factory(),
            'locale' => fn (array $attributes): ?string => Locale::query()->find($attributes['locale_id'])?->code,
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'status' => TranslationStatus::HumanReviewed,
            'source_hash' => null,
        ];
    }
}
