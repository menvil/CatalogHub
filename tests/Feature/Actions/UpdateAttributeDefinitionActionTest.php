<?php

namespace Tests\Feature\Actions;

use App\Actions\CategorySchema\UpdateAttributeDefinitionAction;
use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateAttributeDefinitionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_attribute_definition(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'name' => 'Old name',
            'code' => 'old_code',
            'data_type' => AttributeDataType::String,
            'is_filterable' => false,
        ]);

        app(UpdateAttributeDefinitionAction::class)->handle($attribute, [
            'name' => 'Refresh rate',
            'code' => 'refresh_rate',
            'data_type' => AttributeDataType::Integer->value,
            'dimension' => 'frequency',
            'canonical_unit' => 'hertz',
            'position' => 4,
            'is_filterable' => true,
            'is_visible' => false,
        ]);

        $attribute->refresh();

        $this->assertSame('Refresh rate', $attribute->name);
        $this->assertSame('refresh_rate', $attribute->code);
        $this->assertSame(AttributeDataType::Integer, $attribute->data_type);
        $this->assertSame('frequency', $attribute->dimension);
        $this->assertSame('hertz', $attribute->canonical_unit);
        $this->assertSame(4, $attribute->position);
        $this->assertTrue($attribute->is_filterable);
        $this->assertFalse($attribute->is_visible);
    }

    public function test_allows_keeping_current_attribute_code(): void
    {
        $attribute = AttributeDefinition::factory()->create(['code' => 'refresh_rate']);

        app(UpdateAttributeDefinitionAction::class)->handle($attribute, [
            'name' => 'Refresh rate',
            'code' => 'refresh_rate',
            'data_type' => AttributeDataType::Integer->value,
        ]);

        $this->assertSame('Refresh rate', $attribute->fresh()->name);
    }

    public function test_rejects_duplicate_attribute_code_inside_category(): void
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        AttributeDefinition::factory()->for($category, 'category')->for($section, 'section')->create(['code' => 'weight']);
        $attribute = AttributeDefinition::factory()->for($category, 'category')->for($section, 'section')->create(['code' => 'height']);

        $this->expectException(ValidationException::class);

        app(UpdateAttributeDefinitionAction::class)->handle($attribute, [
            'name' => 'Height',
            'code' => 'weight',
            'data_type' => AttributeDataType::Decimal->value,
        ]);
    }
}
