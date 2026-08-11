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

    public function active(): static
    {
        return $this->state(fn (): array => ['is_active' => true]);
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
            'is_default' => false,
        ]);
    }
}
