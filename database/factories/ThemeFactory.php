<?php

namespace Database\Factories;

use App\Enums\ThemeStatus;
use App\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Theme> */
class ThemeFactory extends Factory
{
    protected $model = Theme::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('theme_????????'),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'status' => ThemeStatus::default(),
            'version' => '1.0.0',
            'preview_image_path' => null,
            'is_system' => false,
            'config_json' => [],
        ];
    }
}
