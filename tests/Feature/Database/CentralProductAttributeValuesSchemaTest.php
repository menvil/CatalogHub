<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CentralProductAttributeValuesSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_central_product_attribute_values_table(): void
    {
        $this->assertTrue(Schema::hasTable('central_product_attribute_values'));

        foreach ([
            'id',
            'central_product_id',
            'attribute_definition_id',
            'raw_value',
            'value_type',
            'value_text',
            'value_number',
            'value_bool',
            'value_enum_code',
            'value_json',
            'value_min',
            'value_max',
            'source_unit',
            'canonical_value',
            'canonical_unit',
            'confidence',
            'source_type',
            'source_id',
            'source_reference',
            'created_at',
            'updated_at',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('central_product_attribute_values', $column),
                "Missing column [{$column}].",
            );
        }
    }
}
