<?php

namespace Database\Factories;

use App\Enums\TranslationStatus;
use App\Models\Locale;
use App\Models\MeasurementUnit;
use App\Models\Translations\UnitTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UnitTranslation> */
class UnitTranslationFactory extends Factory
{
    protected $model = UnitTranslation::class;

    public function definition(): array
    {
        return [
            'measurement_unit_id' => MeasurementUnit::factory(),
            'locale_id' => Locale::factory(),
            'locale' => 'de-DE',
            'short_name' => fake()->lexify('??'),
            'long_name' => fake()->word(),
            'plural_name' => fake()->word(),
            'symbol_position' => 'after',
            'space_between_value_and_unit' => true,
            'status' => TranslationStatus::HumanReviewed,
            'source_hash' => null,
        ];
    }
}
