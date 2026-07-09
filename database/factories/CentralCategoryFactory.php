<?php

namespace Database\Factories;

use App\Enums\CentralCategoryStatus;
use App\Enums\CategorySchemaStatus;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CentralCategory>
 */
class CentralCategoryFactory extends Factory
{
    protected $model = CentralCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => str($name)->headline()->toString(),
            'slug' => Str::slug($name),
            'status' => CentralCategoryStatus::default(),
            'schema_status' => CategorySchemaStatus::default(),
            'position' => 0,
        ];
    }
}
