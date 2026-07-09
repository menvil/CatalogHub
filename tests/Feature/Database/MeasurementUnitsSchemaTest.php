<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MeasurementUnitsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_measurement_units_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('measurement_units'));
        $this->assertTrue(Schema::hasColumns('measurement_units', [
            'id',
            'dimension_id',
            'code',
            'symbol',
            'name',
            'system',
            'factor_to_canonical',
            'offset_to_canonical',
            'precision_default',
            'aliases_json',
            'is_canonical',
            'is_active',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_measurement_units_have_expected_indexes(): void
    {
        $indexes = collect(Schema::getIndexes('measurement_units'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['dimension_id', 'code']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['code']
        ));
    }
}
