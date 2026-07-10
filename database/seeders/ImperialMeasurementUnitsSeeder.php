<?php

namespace Database\Seeders;

use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use Illuminate\Database\Seeder;

class ImperialMeasurementUnitsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->units() as $unit) {
            $dimension = MeasurementDimension::query()->where('code', $unit['dimension'])->firstOrFail();

            MeasurementUnit::updateOrCreate(
                [
                    'dimension_id' => $dimension->id,
                    'code' => $unit['code'],
                ],
                [
                    'symbol' => $unit['symbol'],
                    'name' => $unit['name'],
                    'system' => 'imperial',
                    'factor_to_canonical' => $unit['factor_to_canonical'],
                    'offset_to_canonical' => $unit['offset_to_canonical'],
                    'precision_default' => $unit['precision_default'],
                    'aliases_json' => $unit['aliases_json'],
                    'is_canonical' => false,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function units(): array
    {
        return [
            [
                'dimension' => 'length',
                'code' => 'inch',
                'symbol' => '"',
                'name' => 'Inch',
                'factor_to_canonical' => '25.4',
                'offset_to_canonical' => '0',
                'precision_default' => 2,
                'aliases_json' => ['"', 'in', 'inch', 'inches'],
            ],
            [
                'dimension' => 'length',
                'code' => 'foot',
                'symbol' => 'ft',
                'name' => 'Foot',
                'factor_to_canonical' => '304.8',
                'offset_to_canonical' => '0',
                'precision_default' => 2,
                'aliases_json' => ['ft', 'foot', 'feet'],
            ],
            [
                'dimension' => 'mass',
                'code' => 'pound',
                'symbol' => 'lb',
                'name' => 'Pound',
                'factor_to_canonical' => '0.45359237',
                'offset_to_canonical' => '0',
                'precision_default' => 2,
                'aliases_json' => ['lb', 'lbs', 'pound', 'pounds', 'фунт', 'фунта', 'фунтов'],
            ],
            [
                'dimension' => 'mass',
                'code' => 'ounce',
                'symbol' => 'oz',
                'name' => 'Ounce',
                'factor_to_canonical' => '0.0283495231',
                'offset_to_canonical' => '0',
                'precision_default' => 2,
                'aliases_json' => ['oz', 'ounce', 'ounces'],
            ],
            [
                'dimension' => 'volume',
                'code' => 'gallon_us',
                'symbol' => 'gal',
                'name' => 'US Gallon',
                'factor_to_canonical' => '3.785411784',
                'offset_to_canonical' => '0',
                'precision_default' => 2,
                'aliases_json' => ['gal', 'gallon', 'gallons', 'gallon_us', 'us gallon', 'us gallons'],
            ],
            [
                'dimension' => 'temperature',
                'code' => 'fahrenheit',
                'symbol' => '°F',
                'name' => 'Fahrenheit',
                'factor_to_canonical' => '0.5555555556',
                'offset_to_canonical' => '-17.7777777778',
                'precision_default' => 1,
                'aliases_json' => ['f', 'fahrenheit', '°f'],
            ],
        ];
    }
}
