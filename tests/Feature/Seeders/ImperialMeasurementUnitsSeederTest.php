<?php

namespace Tests\Feature\Seeders;

use App\Models\MeasurementUnit;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImperialMeasurementUnitsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_imperial_units_idempotently(): void
    {
        $this->seed(MeasurementDimensionsSeeder::class);
        $this->seed(ImperialMeasurementUnitsSeeder::class);
        $this->seed(ImperialMeasurementUnitsSeeder::class);

        foreach (['inch', 'foot', 'pound', 'ounce', 'gallon_us', 'fahrenheit'] as $code) {
            $this->assertDatabaseHas('measurement_units', ['code' => $code]);
        }

        $fahrenheit = MeasurementUnit::query()->where('code', 'fahrenheit')->firstOrFail();

        $this->assertSame('0.5555555556', $fahrenheit->factor_to_canonical);
        $this->assertSame('-17.7777777778', $fahrenheit->offset_to_canonical);
    }
}
