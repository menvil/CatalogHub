<?php

namespace Tests\Feature\Actions;

use App\Actions\CategorySchema\CloneCategorySchemaAction;
use App\Enums\AttributeDataType;
use App\Enums\CategorySchemaStatus;
use App\Exceptions\CategorySchema\CannotCloneCategorySchemaException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloneCategorySchemaActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->centralAdmin()->create());
    }

    public function test_clones_category_schema_sections_attributes_and_options(): void
    {
        $source = CentralCategory::factory()->create(['schema_status' => CategorySchemaStatus::Approved]);
        $target = CentralCategory::factory()->create(['schema_status' => CategorySchemaStatus::Draft]);

        $section = AttributeSection::factory()->for($source, 'category')->create([
            'code' => 'display',
            'position' => 2,
        ]);
        $attribute = AttributeDefinition::factory()
            ->assignedTo($source)
            ->state(['attribute_section_id' => $section->id])
            ->create([
                'code' => 'panel_type',
                'data_type' => AttributeDataType::Enum,
                'position' => 1,
                'is_searchable' => true,
            ]);
        AttributeOption::factory()->for($attribute, 'attribute')->create([
            'code' => 'ips',
            'position' => 1,
        ]);

        app(CloneCategorySchemaAction::class)->handle($source, $target);

        $this->assertDatabaseHas('attribute_sections', [
            'central_category_id' => $target->id,
            'code' => 'display',
            'position' => 2,
        ]);
        $clonedAssignment = $target->attributeAssignments()->sole();
        $clonedAttribute = $clonedAssignment->definition;
        self::assertSame($attribute->id, $clonedAttribute->id);
        self::assertSame(1, $clonedAssignment->position);
        self::assertTrue($clonedAssignment->is_searchable);
        self::assertSame(1, AttributeDefinition::query()->count());
        $this->assertDatabaseHas('attribute_options', [
            'attribute_definition_id' => $clonedAttribute->id,
            'code' => 'ips',
            'position' => 1,
        ]);
        $this->assertSame(CategorySchemaStatus::Draft, $target->fresh()->schema_status);
    }

    public function test_nested_sections_cannot_enter_target_clone_data(): void
    {
        $source = CentralCategory::factory()->create();
        $parent = AttributeSection::factory()->for($source, 'category')->create();
        $this->expectException(QueryException::class);
        AttributeSection::factory()->for($source, 'category')->for($parent, 'parent')->create();
    }

    public function test_clones_sectionless_attributes_and_options(): void
    {
        $source = CentralCategory::factory()->create();
        $target = CentralCategory::factory()->create();
        $attribute = AttributeDefinition::factory()->assignedTo($source)->create([
            'attribute_section_id' => null,
            'code' => 'loose_attribute',
            'data_type' => AttributeDataType::Enum,
        ]);
        AttributeOption::factory()->for($attribute, 'attribute')->create(['code' => 'yes']);

        app(CloneCategorySchemaAction::class)->handle($source, $target);

        $assignment = $target->attributeAssignments()->sole();
        $clonedAttribute = $assignment->definition;
        self::assertSame($attribute->id, $clonedAttribute->id);
        self::assertNull($assignment->attribute_section_id);
        self::assertSame(1, AttributeOption::query()->count());
        $this->assertDatabaseHas('attribute_options', [
            'attribute_definition_id' => $clonedAttribute->id,
            'code' => 'yes',
        ]);
    }

    public function test_does_not_clone_schema_to_same_category(): void
    {
        $category = CentralCategory::factory()->create();

        $this->expectException(CannotCloneCategorySchemaException::class);

        app(CloneCategorySchemaAction::class)->handle($category, $category);
    }

    public function test_does_not_clone_into_non_empty_target_schema(): void
    {
        $source = CentralCategory::factory()->create();
        $target = CentralCategory::factory()->create();
        AttributeSection::factory()->for($target, 'category')->create();

        $this->expectException(CannotCloneCategorySchemaException::class);

        app(CloneCategorySchemaAction::class)->handle($source, $target);
    }
}
