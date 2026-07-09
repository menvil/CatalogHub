<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeOptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_attribute_option_belongs_to_attribute_definition(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'data_type' => 'enum',
        ]);

        $option = AttributeOption::factory()
            ->for($attribute, 'attribute')
            ->create();

        $this->assertTrue($option->attribute->is($attribute));
    }

    public function test_attribute_definition_has_options(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'data_type' => 'enum',
        ]);
        $option = AttributeOption::factory()->for($attribute, 'attribute')->create();

        $this->assertTrue($attribute->options->first()->is($option));
    }

    public function test_attribute_option_visibility_is_cast_to_boolean(): void
    {
        $option = AttributeOption::factory()->create([
            'is_visible' => 1,
        ]);

        $this->assertTrue($option->is_visible);
    }
}
