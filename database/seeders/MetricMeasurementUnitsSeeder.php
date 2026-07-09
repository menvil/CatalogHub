<?php

namespace Database\Seeders;

use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use Illuminate\Database\Seeder;

class MetricMeasurementUnitsSeeder extends Seeder
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
                    'system' => $unit['system'],
                    'factor_to_canonical' => $unit['factor_to_canonical'],
                    'offset_to_canonical' => $unit['offset_to_canonical'],
                    'precision_default' => $unit['precision_default'],
                    'aliases_json' => $unit['aliases_json'],
                    'is_canonical' => $unit['is_canonical'],
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
                'code' => 'millimeter',
                'symbol' => 'mm',
                'name' => 'Millimeter',
                'system' => 'metric',
                'factor_to_canonical' => '1',
                'offset_to_canonical' => '0',
                'precision_default' => 1,
                'aliases_json' => ['mm', 'millimeter', 'millimeters', 'мм'],
                'is_canonical' => true,
            ],
            [
                'dimension' => 'length',
                'code' => 'centimeter',
                'symbol' => 'cm',
                'name' => 'Centimeter',
                'system' => 'metric',
                'factor_to_canonical' => '10',
                'offset_to_canonical' => '0',
                'precision_default' => 1,
                'aliases_json' => ['cm', 'centimeter', 'centimeters', 'см'],
                'is_canonical' => false,
            ],
            [
                'dimension' => 'length',
                'code' => 'meter',
                'symbol' => 'm',
                'name' => 'Meter',
                'system' => 'metric',
                'factor_to_canonical' => '1000',
                'offset_to_canonical' => '0',
                'precision_default' => 2,
                'aliases_json' => ['m', 'meter', 'meters', 'metre', 'metres'],
                'is_canonical' => false,
            ],
            [
                'dimension' => 'mass',
                'code' => 'gram',
                'symbol' => 'g',
                'name' => 'Gram',
                'system' => 'metric',
                'factor_to_canonical' => '0.001',
                'offset_to_canonical' => '0',
                'precision_default' => 0,
                'aliases_json' => ['g', 'gram', 'grams', 'г'],
                'is_canonical' => false,
            ],
            [
                'dimension' => 'mass',
                'code' => 'kilogram',
                'symbol' => 'kg',
                'name' => 'Kilogram',
                'system' => 'metric',
                'factor_to_canonical' => '1',
                'offset_to_canonical' => '0',
                'precision_default' => 2,
                'aliases_json' => ['kg', 'kilogram', 'kilograms', 'кг'],
                'is_canonical' => true,
            ],
            [
                'dimension' => 'volume',
                'code' => 'milliliter',
                'symbol' => 'ml',
                'name' => 'Milliliter',
                'system' => 'metric',
                'factor_to_canonical' => '0.001',
                'offset_to_canonical' => '0',
                'precision_default' => 0,
                'aliases_json' => ['ml', 'milliliter', 'milliliters', 'millilitre', 'millilitres', 'мл'],
                'is_canonical' => false,
            ],
            [
                'dimension' => 'volume',
                'code' => 'liter',
                'symbol' => 'l',
                'name' => 'Liter',
                'system' => 'metric',
                'factor_to_canonical' => '1',
                'offset_to_canonical' => '0',
                'precision_default' => 2,
                'aliases_json' => ['l', 'liter', 'liters', 'litre', 'litres', 'литр', 'литра', 'литров'],
                'is_canonical' => true,
            ],
            [
                'dimension' => 'power',
                'code' => 'watt',
                'symbol' => 'W',
                'name' => 'Watt',
                'system' => 'metric',
                'factor_to_canonical' => '1',
                'offset_to_canonical' => '0',
                'precision_default' => 0,
                'aliases_json' => ['W', 'watt', 'watts', 'Вт', 'ватт'],
                'is_canonical' => true,
            ],
            [
                'dimension' => 'power',
                'code' => 'kilowatt',
                'symbol' => 'kW',
                'name' => 'Kilowatt',
                'system' => 'metric',
                'factor_to_canonical' => '1000',
                'offset_to_canonical' => '0',
                'precision_default' => 2,
                'aliases_json' => ['kW', 'kilowatt', 'kilowatts', 'кВт', 'киловатт'],
                'is_canonical' => false,
            ],
            [
                'dimension' => 'temperature',
                'code' => 'celsius',
                'symbol' => '°C',
                'name' => 'Celsius',
                'system' => 'metric',
                'factor_to_canonical' => '1',
                'offset_to_canonical' => '0',
                'precision_default' => 1,
                'aliases_json' => ['c', 'celsius', '°c'],
                'is_canonical' => true,
            ],
            [
                'dimension' => 'temperature',
                'code' => 'kelvin',
                'symbol' => 'K',
                'name' => 'Kelvin',
                'system' => 'metric',
                'factor_to_canonical' => '1',
                'offset_to_canonical' => '-273.15',
                'precision_default' => 1,
                'aliases_json' => ['K', 'kelvin'],
                'is_canonical' => false,
            ],
            [
                'dimension' => 'pressure',
                'code' => 'bar',
                'symbol' => 'bar',
                'name' => 'Bar',
                'system' => 'metric',
                'factor_to_canonical' => '1',
                'offset_to_canonical' => '0',
                'precision_default' => 2,
                'aliases_json' => ['bar', 'bars'],
                'is_canonical' => true,
            ],
            [
                'dimension' => 'frequency',
                'code' => 'hertz',
                'symbol' => 'Hz',
                'name' => 'Hertz',
                'system' => 'metric',
                'factor_to_canonical' => '1',
                'offset_to_canonical' => '0',
                'precision_default' => 0,
                'aliases_json' => ['Hz', 'hertz'],
                'is_canonical' => true,
            ],
            [
                'dimension' => 'frequency',
                'code' => 'kilohertz',
                'symbol' => 'kHz',
                'name' => 'Kilohertz',
                'system' => 'metric',
                'factor_to_canonical' => '1000',
                'offset_to_canonical' => '0',
                'precision_default' => 2,
                'aliases_json' => ['kHz', 'kilohertz'],
                'is_canonical' => false,
            ],
        ];
    }
}
