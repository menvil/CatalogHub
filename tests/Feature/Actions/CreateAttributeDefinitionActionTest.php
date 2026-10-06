<?php

namespace Tests\Feature\Actions;

use App\Actions\CategorySchema\CreateAttributeDefinitionAction;
use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateAttributeDefinitionActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->centralAdmin()->create());
    }

    public function test_creates_attribute_definition_inside_section(): void
    {
        $section = AttributeSection::factory()->create();

        $attribute = app(CreateAttributeDefinitionAction::class)->handle($section, [
            'name' => 'Refresh rate',
            'code' => 'refresh_rate',
            'data_type' => AttributeDataType::Integer->value,
            'is_searchable' => true,
        ]);

        $this->assertTrue($attribute->assignments()->sole()->section->is($section));
        $this->assertTrue($attribute->assignments()->sole()->category->is($section->category));
        $this->assertSame('refresh_rate', $attribute->code);
        $this->assertSame(AttributeDataType::Integer, $attribute->data_type);
        $this->assertTrue($attribute->assignments()->sole()->is_searchable);
        $this->assertDatabaseHas('category_attribute_assignments', [
            'central_category_id' => $section->central_category_id,
            'attribute_section_id' => $section->id,
            'attribute_definition_id' => $attribute->id,
            'position' => 0,
        ]);
    }

    public function test_rejects_duplicate_attribute_code_inside_category(): void
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        AttributeDefinition::factory()->assignedTo($category)->state(['attribute_section_id' => $section->id])->create(['code' => 'weight']);

        $this->expectException(ValidationException::class);

        app(CreateAttributeDefinitionAction::class)->handle($section, [
            'name' => 'Weight',
            'code' => 'weight',
            'data_type' => AttributeDataType::Decimal->value,
        ]);
    }

    public function test_rejects_invalid_attribute_data_type(): void
    {
        $section = AttributeSection::factory()->create();

        $this->expectException(ValidationException::class);

        app(CreateAttributeDefinitionAction::class)->handle($section, [
            'name' => 'Refresh rate',
            'code' => 'refresh_rate',
            'data_type' => 'unsupported',
        ]);
    }

    public function test_rejects_invalid_attribute_code(): void
    {
        $section = AttributeSection::factory()->create();

        $this->expectException(ValidationException::class);

        app(CreateAttributeDefinitionAction::class)->handle($section, [
            'name' => 'Refresh rate',
            'code' => 'Refresh Rate',
            'data_type' => AttributeDataType::Integer->value,
        ]);
    }

    public function test_rejects_explicit_position_above_portable_integer_range(): void
    {
        $section = AttributeSection::factory()->create();
        AttributeDefinition::factory()
            ->assignedTo($section->category)
            ->state(['attribute_section_id' => $section->id])
            ->create(['position' => AttributeDefinition::MAX_POSITION]);

        $this->expectException(ValidationException::class);

        app(CreateAttributeDefinitionAction::class)->handle($section, [
            'name' => 'Refresh rate',
            'code' => 'refresh_rate',
            'data_type' => AttributeDataType::Integer->value,
            'position' => AttributeDefinition::MAX_POSITION + 1,
        ]);
    }
}
