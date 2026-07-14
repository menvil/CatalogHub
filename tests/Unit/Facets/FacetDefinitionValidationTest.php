<?php

namespace Tests\Unit\Facets;

use App\Enums\FacetSourceType;
use App\Enums\FacetType;
use App\Rules\Facets\ValidFacetDefinitionRule;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FacetDefinitionValidationTest extends TestCase
{
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
}
