<?php

namespace Database\Seeders;

use App\Models\MeasurementDimension;
use Illuminate\Database\Seeder;

class MeasurementDimensionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->dimensions() as $dimension) {
            MeasurementDimension::updateOrCreate(
                ['code' => $dimension['code']],
                $dimension,
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dimensions(): array
    {
        return [
            [
                'code' => 'length',
                'name' => 'Length',
                'description' => 'Linear distance and size.',
                'base_unit_code' => 'millimeter',
                'sort_order' => 10,
                'is_active' => true,
            ],
            [
                'code' => 'mass',
                'name' => 'Mass',
                'description' => 'Weight and mass values.',
                'base_unit_code' => 'kilogram',
                'sort_order' => 20,
                'is_active' => true,
            ],
            [
                'code' => 'volume',
                'name' => 'Volume',
                'description' => 'Liquid and container volume.',
                'base_unit_code' => 'liter',
                'sort_order' => 30,
                'is_active' => true,
            ],
            [
                'code' => 'power',
                'name' => 'Power',
                'description' => 'Electrical or mechanical power.',
                'base_unit_code' => 'watt',
                'sort_order' => 40,
                'is_active' => true,
            ],
            [
                'code' => 'temperature',
                'name' => 'Temperature',
                'description' => 'Thermal temperature.',
                'base_unit_code' => 'celsius',
                'sort_order' => 50,
                'is_active' => true,
            ],
            [
                'code' => 'pressure',
                'name' => 'Pressure',
                'description' => 'Pressure values.',
                'base_unit_code' => 'bar',
                'sort_order' => 60,
                'is_active' => true,
            ],
            [
                'code' => 'frequency',
                'name' => 'Frequency',
                'description' => 'Frequency values.',
                'base_unit_code' => 'hertz',
                'sort_order' => 70,
                'is_active' => true,
            ],
        ];
    }
}
