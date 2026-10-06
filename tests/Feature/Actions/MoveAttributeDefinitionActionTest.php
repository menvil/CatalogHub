<?php

namespace Tests\Feature\Actions;

use App\Actions\CategorySchema\MoveAttributeDefinitionAction;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MoveAttributeDefinitionActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->centralAdmin()->create());
    }

    public function test_moves_attribute_definition_to_another_section(): void
    {
        $category = CentralCategory::factory()->create();
        $source = AttributeSection::factory()->for($category, 'category')->create();
        $target = AttributeSection::factory()->for($category, 'category')->create();

        $attribute = AttributeDefinition::factory()
            ->assignedTo($category)
            ->state(['attribute_section_id' => $source->id])
            ->create(['position' => 1])->assignments()->sole();
        $targetAttribute = AttributeDefinition::factory()
            ->assignedTo($category)
            ->state(['attribute_section_id' => $target->id])
            ->create(['code' => 'target_attribute', 'position' => 3])->assignments()->sole();

        $movedAttribute = app(MoveAttributeDefinitionAction::class)->handle($attribute, $target, 0);

        $attribute->refresh();

        $this->assertTrue($attribute->section->is($target));
        $this->assertSame(0, $attribute->position);
        $this->assertTrue($movedAttribute->section->is($target));
        $this->assertSame(0, $movedAttribute->position);
        $this->assertSame(1, $targetAttribute->fresh()->position);
    }

    public function test_closes_source_section_position_gap_after_move(): void
    {
        $category = CentralCategory::factory()->create();
        $source = AttributeSection::factory()->for($category, 'category')->create();
        $target = AttributeSection::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()->assignedTo($category)->state(['attribute_section_id' => $source->id])->create(['code' => 'a', 'position' => 1])->assignments()->sole();
        $remaining = AttributeDefinition::factory()->assignedTo($category)->state(['attribute_section_id' => $source->id])->create(['code' => 'b', 'position' => 2])->assignments()->sole();

        app(MoveAttributeDefinitionAction::class)->handle($attribute, $target, 0);

        $this->assertSame(0, $remaining->fresh()->position);
    }

    public function test_reorders_attributes_inside_same_section(): void
    {
        $section = AttributeSection::factory()->create();
        $first = AttributeDefinition::factory()->assignedTo($section->category)->state(['attribute_section_id' => $section->id])->create(['code' => 'a', 'position' => 1])->assignments()->sole();
        $second = AttributeDefinition::factory()->assignedTo($section->category)->state(['attribute_section_id' => $section->id])->create(['code' => 'b', 'position' => 2])->assignments()->sole();
        $third = AttributeDefinition::factory()->assignedTo($section->category)->state(['attribute_section_id' => $section->id])->create(['code' => 'c', 'position' => 3])->assignments()->sole();

        app(MoveAttributeDefinitionAction::class)->handle($third, $section, 0);

        $this->assertSame(1, $first->fresh()->position);
        $this->assertSame(2, $second->fresh()->position);
        $this->assertSame(0, $third->fresh()->position);
    }

    public function test_does_not_move_attribute_to_section_from_another_category(): void
    {
        $source = AttributeSection::factory()->create();
        $target = AttributeSection::factory()->create();
        $attribute = AttributeDefinition::factory()
            ->assignedTo($source->category)
            ->state(['attribute_section_id' => $source->id])
            ->create()->assignments()->sole();

        $this->expectException(ValidationException::class);

        app(MoveAttributeDefinitionAction::class)->handle($attribute, $target, 0);
    }

    public function test_does_not_move_attribute_to_negative_position(): void
    {
        $section = AttributeSection::factory()->create();
        $attribute = AttributeDefinition::factory()
            ->assignedTo($section->category)
            ->state(['attribute_section_id' => $section->id])
            ->create()->assignments()->sole();

        $this->expectException(ValidationException::class);

        app(MoveAttributeDefinitionAction::class)->handle($attribute, $section, -1);
    }

    public function test_does_not_move_attribute_above_max_position(): void
    {
        $section = AttributeSection::factory()->create();
        $attribute = AttributeDefinition::factory()
            ->assignedTo($section->category)
            ->state(['attribute_section_id' => $section->id])
            ->create()->assignments()->sole();

        $this->expectException(ValidationException::class);

        app(MoveAttributeDefinitionAction::class)->handle($attribute, $section, AttributeDefinition::MAX_POSITION + 1);
    }

    public function test_does_not_move_attribute_when_target_shift_would_overflow(): void
    {
        $category = CentralCategory::factory()->create();
        $source = AttributeSection::factory()->for($category, 'category')->create();
        $target = AttributeSection::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()
            ->assignedTo($category)
            ->state(['attribute_section_id' => $source->id])
            ->create(['position' => 1])->assignments()->sole();
        AttributeDefinition::factory()
            ->assignedTo($category)
            ->state(['attribute_section_id' => $target->id])
            ->create(['code' => 'max_position_attribute', 'position' => AttributeDefinition::MAX_POSITION])->assignments()->sole();

        $this->expectException(ValidationException::class);

        app(MoveAttributeDefinitionAction::class)->handle($attribute, $target, AttributeDefinition::MAX_POSITION);
    }
}
