<?php

namespace Tests\Feature\Seeders;

use App\Models\MeasurementDimension;
use Database\Seeders\MeasurementDimensionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementDimensionsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_base_dimensions_idempotently(): void
    {
        $this->seed(MeasurementDimensionsSeeder::class);
        $this->seed(MeasurementDimensionsSeeder::class);

        $this->assertSame(7, MeasurementDimension::query()->count());

        foreach (['length', 'mass', 'volume', 'power', 'temperature', 'pressure', 'frequency'] as $code) {
            $this->assertDatabaseHas('measurement_dimensions', ['code' => $code]);
        }
    }
}
