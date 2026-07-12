<?php

namespace Database\Factories;

use App\Models\Locale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Locale>
 */
class LocaleFactory extends Factory
{
    protected $model = Locale::class;

    public function definition(): array
    {
        $languageCode = fake()->unique()->lexify('??');
        $regionCode = strtoupper(fake()->lexify('??'));
        $code = "{$languageCode}-{$regionCode}";

        return [
            'code' => $code,
            'language_code' => $languageCode,
            'region_code' => $regionCode,
            'name' => fake()->words(2, true),
            'native_name' => fake()->optional()->words(2, true),
            'direction' => 'ltr',
            'is_active' => true,
            'is_default' => false,
            'position' => 0,
        ];
    }
}
