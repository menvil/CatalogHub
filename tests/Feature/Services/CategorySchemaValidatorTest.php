<?php

namespace Tests\Feature\Services;

use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Services\CategorySchema\CategorySchemaValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySchemaValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_empty_section_warning(): void
    {
        $category = CentralCategory::factory()->create();
        AttributeSection::factory()->for($category, 'category')->create();

        $result = app(CategorySchemaValidator::class)->validate($category);

        $this->assertFalse($result->hasErrors());
        $this->assertTrue($result->hasWarnings());
        $this->assertTrue($result->hasIssueCode('empty_section'));
    }

    public function test_detects_enum_attribute_without_visible_options_warning(): void
    {
        $category = CentralCategory::factory()->create();
        $attribute = AttributeDefinition::factory()->for($category, 'category')->create([
            'data_type' => AttributeDataType::Enum,
        ]);
        AttributeOption::factory()->for($attribute, 'attribute')->create(['is_visible' => false]);

        $result = app(CategorySchemaValidator::class)->validate($category);

        $this->assertTrue($result->hasWarnings());
        $this->assertTrue($result->hasIssueCode('enum_without_visible_options'));
    }

    public function test_detects_options_on_non_enum_attribute_error(): void
    {
        $category = CentralCategory::factory()->create();
        $attribute = AttributeDefinition::factory()->for($category, 'category')->create([
            'data_type' => AttributeDataType::Decimal,
        ]);
        AttributeOption::factory()->for($attribute, 'attribute')->create();

        $result = app(CategorySchemaValidator::class)->validate($category);

        $this->assertTrue($result->hasErrors());
        $this->assertTrue($result->hasIssueCode('options_on_non_enum_attribute'));
    }

    public function test_detects_hidden_required_attribute_warning(): void
    {
        $category = CentralCategory::factory()->create();
        AttributeDefinition::factory()->for($category, 'category')->create([
            'is_required' => true,
            'is_visible' => false,
        ]);

        $result = app(CategorySchemaValidator::class)->validate($category);

        $this->assertFalse($result->hasErrors());
        $this->assertTrue($result->hasWarnings());
        $this->assertTrue($result->hasIssueCode('hidden_required_attribute'));
    }

    public function test_detects_filterable_and_sortable_text_or_json_warnings(): void
    {
        $category = CentralCategory::factory()->create();
        AttributeDefinition::factory()->for($category, 'category')->create([
            'data_type' => AttributeDataType::Json,
            'is_filterable' => true,
            'is_sortable' => true,
        ]);

        $result = app(CategorySchemaValidator::class)->validate($category);

        $this->assertFalse($result->hasErrors());
        $this->assertTrue($result->hasWarnings());
        $this->assertTrue($result->hasIssueCode('filterable_complex_attribute'));
        $this->assertTrue($result->hasIssueCode('sortable_complex_attribute'));
    }
}
