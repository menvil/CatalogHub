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
        $targetAttribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($target, 'section')
            ->create(['code' => 'target_attribute', 'position' => 3]);

        $movedAttribute = app(MoveAttributeDefinitionAction::class)->handle($attribute, $target, 3);

        $attribute->refresh();

        $this->assertTrue($attribute->section->is($target));
        $this->assertSame(3, $attribute->position);
        $this->assertTrue($movedAttribute->section->is($target));
        $this->assertSame(3, $movedAttribute->position);
        $this->assertSame(4, $targetAttribute->fresh()->position);
    }

    public function test_closes_source_section_position_gap_after_move(): void
    {
        $category = CentralCategory::factory()->create();
        $source = AttributeSection::factory()->for($category, 'category')->create();
        $target = AttributeSection::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()->for($category, 'category')->for($source, 'section')->create(['code' => 'a', 'position' => 1]);
        $remaining = AttributeDefinition::factory()->for($category, 'category')->for($source, 'section')->create(['code' => 'b', 'position' => 2]);

        app(MoveAttributeDefinitionAction::class)->handle($attribute, $target, 1);

        $this->assertSame(1, $remaining->fresh()->position);
    }

    public function test_reorders_attributes_inside_same_section(): void
    {
        $section = AttributeSection::factory()->create();
        $first = AttributeDefinition::factory()->for($section->category, 'category')->for($section, 'section')->create(['code' => 'a', 'position' => 1]);
        $second = AttributeDefinition::factory()->for($section->category, 'category')->for($section, 'section')->create(['code' => 'b', 'position' => 2]);
        $third = AttributeDefinition::factory()->for($section->category, 'category')->for($section, 'section')->create(['code' => 'c', 'position' => 3]);

        app(MoveAttributeDefinitionAction::class)->handle($third, $section, 1);

        $this->assertSame(2, $first->fresh()->position);
        $this->assertSame(3, $second->fresh()->position);
        $this->assertSame(1, $third->fresh()->position);
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

    public function test_does_not_move_attribute_above_max_position(): void
    {
        $section = AttributeSection::factory()->create();
        $attribute = AttributeDefinition::factory()
            ->for($section->category, 'category')
            ->for($section, 'section')
            ->create();

        $this->expectException(CannotMoveAttributeDefinitionException::class);
        $this->expectExceptionMessage('between zero and the maximum unsigned integer value');

        app(MoveAttributeDefinitionAction::class)->handle($attribute, $section, AttributeDefinition::MAX_POSITION + 1);
    }

    public function test_does_not_move_attribute_when_target_shift_would_overflow(): void
    {
        $category = CentralCategory::factory()->create();
        $source = AttributeSection::factory()->for($category, 'category')->create();
        $target = AttributeSection::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($source, 'section')
            ->create(['position' => 1]);
        AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($target, 'section')
            ->create(['code' => 'max_position_attribute', 'position' => AttributeDefinition::MAX_POSITION]);

        $this->expectException(CannotMoveAttributeDefinitionException::class);

        app(MoveAttributeDefinitionAction::class)->handle($attribute, $target, AttributeDefinition::MAX_POSITION);
    }
}
