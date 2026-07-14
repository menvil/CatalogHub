<?php

namespace Tests\Unit\Facets;

use App\Enums\AttributeDataType;
use App\Enums\FacetSourceType;
use App\Enums\FacetType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Rules\Facets\ValidFacetDefinitionRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FacetDefinitionValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkbox_accepts_attribute_and_brand_sources(): void
    {
        foreach ([FacetSourceType::Attribute, FacetSourceType::Brand] as $source) {
            $validator = Validator::make([
                'source_type' => $source->value,
                'facet_type' => FacetType::Checkbox->value,
            ], [
                'facet_type' => [new ValidFacetDefinitionRule],
            ]);

            $this->assertTrue($validator->passes());
        }
    }

    public function test_checkbox_rejects_rating_source(): void
    {
        $validator = Validator::make([
            'source_type' => FacetSourceType::Rating->value,
            'facet_type' => FacetType::Checkbox->value,
        ], [
            'facet_type' => [new ValidFacetDefinitionRule],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_range_accepts_rating_and_numeric_attributes(): void
    {
        $ratingValidator = $this->validator([
            'source_type' => FacetSourceType::Rating->value,
            'facet_type' => FacetType::Range->value,
        ]);
        $attribute = AttributeDefinition::factory()->create([
            'data_type' => AttributeDataType::Decimal,
        ]);
        $attributeValidator = $this->validator([
            'category_id' => $attribute->central_category_id,
            'attribute_definition_id' => $attribute->id,
            'source_type' => FacetSourceType::Attribute->value,
            'facet_type' => FacetType::Range->value,
        ]);

        $this->assertTrue($ratingValidator->passes());
        $this->assertTrue($attributeValidator->passes());
    }

    public function test_range_rejects_non_numeric_attribute(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'data_type' => AttributeDataType::String,
        ]);

        $validator = $this->validator([
            'category_id' => $attribute->central_category_id,
            'attribute_definition_id' => $attribute->id,
            'source_type' => FacetSourceType::Attribute->value,
            'facet_type' => FacetType::Range->value,
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_boolean_accepts_boolean_attribute_from_selected_category(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'data_type' => AttributeDataType::Boolean,
        ]);

        $validator = $this->validator([
            'category_id' => $attribute->central_category_id,
            'attribute_definition_id' => $attribute->id,
            'source_type' => FacetSourceType::Attribute->value,
            'facet_type' => FacetType::Boolean->value,
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_boolean_rejects_non_boolean_attribute(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'data_type' => AttributeDataType::String,
        ]);

        $validator = $this->validator([
            'category_id' => $attribute->central_category_id,
            'attribute_definition_id' => $attribute->id,
            'source_type' => FacetSourceType::Attribute->value,
            'facet_type' => FacetType::Boolean->value,
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_select_accepts_brand_or_attribute_source(): void
    {
        foreach ([FacetSourceType::Brand, FacetSourceType::Attribute] as $source) {
            $validator = $this->validator([
                'source_type' => $source->value,
                'facet_type' => FacetType::Select->value,
            ]);

            $this->assertTrue($validator->passes());
        }
    }

    public function test_select_rejects_rating_source(): void
    {
        $validator = $this->validator([
            'source_type' => FacetSourceType::Rating->value,
            'facet_type' => FacetType::Select->value,
        ]);

        $this->assertTrue($validator->fails());
    }

    /** @param array<string, mixed> $data */
    private function validator(array $data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data, [
            'facet_type' => [new ValidFacetDefinitionRule],
        ]);
    }
}
