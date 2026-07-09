<?php

namespace Tests\Feature\Actions;

use App\Actions\CategorySchema\MoveAttributeDefinitionAction;
use App\Exceptions\CategorySchema\CannotMoveAttributeDefinitionException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoveAttributeDefinitionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_moves_attribute_definition_to_another_section(): void
    {
        $category = CentralCategory::factory()->create();
        $source = AttributeSection::factory()->for($category, 'category')->create();
        $target = AttributeSection::factory()->for($category, 'category')->create();

        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($source, 'section')
            ->create(['position' => 1]);

        app(MoveAttributeDefinitionAction::class)->handle($attribute, $target, 3);

        $attribute->refresh();

        $this->assertTrue($attribute->section->is($target));
        $this->assertSame(3, $attribute->position);
    }

    public function test_does_not_move_attribute_to_section_from_another_category(): void
    {
        $source = AttributeSection::factory()->create();
        $target = AttributeSection::factory()->create();
        $attribute = AttributeDefinition::factory()
            ->for($source->category, 'category')
            ->for($source, 'section')
            ->create();

        $this->expectException(CannotMoveAttributeDefinitionException::class);

        app(MoveAttributeDefinitionAction::class)->handle($attribute, $target, 0);
    }

    public function test_does_not_move_attribute_to_negative_position(): void
    {
        $section = AttributeSection::factory()->create();
        $attribute = AttributeDefinition::factory()
            ->for($section->category, 'category')
            ->for($section, 'section')
            ->create();

        $this->expectException(CannotMoveAttributeDefinitionException::class);

        app(MoveAttributeDefinitionAction::class)->handle($attribute, $section, -1);
    }
}
