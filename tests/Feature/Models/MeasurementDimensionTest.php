<?php

namespace Tests\Feature\Models;

use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementDimensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_measurement_dimension_can_be_created_and_has_units(): void
    {
        $dimension = MeasurementDimension::factory()->create([
            'code' => 'length',
            'name' => 'Length',
            'is_active' => true,
        ]);

        $unit = MeasurementUnit::factory()->for($dimension, 'dimension')->create([
            'code' => 'millimeter',
        ]);

        $this->assertTrue($dimension->is_active);
        $this->assertTrue($dimension->units->contains($unit));
    }
}
