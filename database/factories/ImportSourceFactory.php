<?php

namespace Database\Factories;

use App\Models\Imports\ImportSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ImportSource>
 */
class ImportSourceFactory extends Factory
{
    protected $model = ImportSource::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'code' => Str::slug($name),
            'name' => Str::headline($name),
            'type' => ImportSource::TYPE_SERIALIZED_PHP,
            'status' => 'active',
            'config_json' => [],
            'description' => fake()->optional()->sentence(),
        ];
    }
}
