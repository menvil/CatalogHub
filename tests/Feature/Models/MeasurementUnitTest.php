<?php

namespace Tests\Feature\Models;

use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_measurement_unit_can_be_created_and_casts_aliases(): void
    {
        $dimension = MeasurementDimension::factory()->create();
        $unit = MeasurementUnit::factory()->for($dimension, 'dimension')->create([
            'code' => 'watt',
            'factor_to_canonical' => '1.5',
            'aliases_json' => ['W', 'watt'],
            'is_canonical' => true,
        ]);

        $this->assertTrue($unit->dimension->is($dimension));
        $this->assertSame(['W', 'watt'], $unit->aliases_json);
        $this->assertTrue($unit->is_canonical);
        $this->assertSame('1.5000000000', $unit->factor_to_canonical);
    }
}
