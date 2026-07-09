<?php

namespace Database\Factories;

use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AttributeSection>
 */
class AttributeSectionFactory extends Factory
{
    protected $model = AttributeSection::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'central_category_id' => CentralCategory::factory(),
            'parent_id' => null,
            'code' => Str::snake($name),
            'name' => str($name)->headline()->toString(),
            'position' => 0,
            'display_style' => 'table',
            'is_collapsible' => true,
            'is_visible' => true,
        ];
    }
}
