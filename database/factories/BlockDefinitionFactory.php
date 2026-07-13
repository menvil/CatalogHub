<?php

namespace Database\Factories;

use App\Enums\BlockStatus;
use App\Models\BlockDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BlockDefinition> */
class BlockDefinitionFactory extends Factory
{
    protected $model = BlockDefinition::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('block_????????'),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'category' => 'content',
            'supported_page_types_json' => ['home'],
            'required_features_json' => [],
            'config_schema_json' => [],
            'view_component' => null,
            'preview_image_path' => null,
            'status' => BlockStatus::default(),
        ];
    }
}
