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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloneCategorySchemaActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_clones_category_schema_sections_attributes_and_options(): void
    {
        $source = CentralCategory::factory()->create(['schema_status' => CategorySchemaStatus::Approved]);
        $target = CentralCategory::factory()->create(['schema_status' => CategorySchemaStatus::Archived]);

        $section = AttributeSection::factory()->for($source, 'category')->create([
            'code' => 'display',
            'position' => 2,
        ]);
        $attribute = AttributeDefinition::factory()
            ->for($source, 'category')
            ->for($section, 'section')
            ->create([
                'code' => 'panel_type',
                'data_type' => AttributeDataType::Enum,
                'position' => 1,
                'is_filterable' => true,
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
        $this->assertDatabaseHas('attribute_definitions', [
            'central_category_id' => $target->id,
            'code' => 'panel_type',
            'data_type' => AttributeDataType::Enum->value,
            'position' => 1,
            'is_filterable' => true,
        ]);

        $clonedAttribute = AttributeDefinition::query()
            ->where('central_category_id', $target->id)
            ->where('code', 'panel_type')
            ->firstOrFail();

        $this->assertDatabaseHas('attribute_options', [
            'attribute_definition_id' => $clonedAttribute->id,
            'code' => 'ips',
            'position' => 1,
        ]);
        $this->assertSame(CategorySchemaStatus::Draft, $target->fresh()->schema_status);
    }

    public function test_clones_nested_sections(): void
    {
        $source = CentralCategory::factory()->create();
        $target = CentralCategory::factory()->create();
        $parent = AttributeSection::factory()->for($source, 'category')->create(['code' => 'display']);
        AttributeSection::factory()
            ->for($source, 'category')
            ->for($parent, 'parent')
            ->create(['code' => 'panel']);

        app(CloneCategorySchemaAction::class)->handle($source, $target);

        $clonedParent = AttributeSection::query()
            ->where('central_category_id', $target->id)
            ->where('code', 'display')
            ->firstOrFail();

        $this->assertDatabaseHas('attribute_sections', [
            'central_category_id' => $target->id,
            'code' => 'panel',
            'parent_id' => $clonedParent->id,
        ]);
    }

    public function test_clones_sectionless_attributes_and_options(): void
    {
        $source = CentralCategory::factory()->create();
        $target = CentralCategory::factory()->create();
        $attribute = AttributeDefinition::factory()->for($source, 'category')->create([
            'attribute_section_id' => null,
            'code' => 'loose_attribute',
            'data_type' => AttributeDataType::Enum,
        ]);
        AttributeOption::factory()->for($attribute, 'attribute')->create(['code' => 'yes']);

        app(CloneCategorySchemaAction::class)->handle($source, $target);

        $clonedAttribute = AttributeDefinition::query()
            ->where('central_category_id', $target->id)
            ->where('code', 'loose_attribute')
            ->firstOrFail();

        $this->assertNull($clonedAttribute->attribute_section_id);
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
