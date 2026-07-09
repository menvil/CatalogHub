<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MeasurementDimensionsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_measurement_dimensions_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('measurement_dimensions'));
        $this->assertTrue(Schema::hasColumns('measurement_dimensions', [
            'id',
            'code',
            'name',
            'description',
            'base_unit_code',
            'sort_order',
            'is_active',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_measurement_dimensions_code_is_unique(): void
    {
        $indexes = collect(Schema::getIndexes('measurement_dimensions'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['code']
        ));
    }
}
