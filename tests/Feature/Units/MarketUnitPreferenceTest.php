<?php

namespace Tests\Feature\Units;

use App\Models\MarketUnitPreference;
use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MarketUnitPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_model_and_relations_work(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        $this->assertTrue(Schema::hasTable('market_unit_preferences'));
        $this->assertTrue(Schema::hasColumns('market_unit_preferences', [
            'id',
            'market_code',
            'dimension_id',
            'preferred_unit_id',
            'created_at',
            'updated_at',
        ]));

        $volume = MeasurementDimension::query()->where('code', 'volume')->firstOrFail();
        $liter = MeasurementUnit::query()->where('code', 'liter')->firstOrFail();
        $gallon = MeasurementUnit::query()->where('code', 'gallon_us')->firstOrFail();

        $de = MarketUnitPreference::create([
            'market_code' => 'DE',
            'dimension_id' => $volume->id,
            'preferred_unit_id' => $liter->id,
        ]);
        $us = MarketUnitPreference::create([
            'market_code' => 'US',
            'dimension_id' => $volume->id,
            'preferred_unit_id' => $gallon->id,
        ]);

        $this->assertTrue($de->dimension->is($volume));
        $this->assertTrue($us->preferredUnit->is($gallon));
    }

    public function test_preferred_unit_must_match_preference_dimension(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        $volume = MeasurementDimension::query()->where('code', 'volume')->firstOrFail();
        $inch = MeasurementUnit::query()->where('code', 'inch')->firstOrFail();

        $this->expectException(QueryException::class);

        MarketUnitPreference::create([
            'market_code' => 'US',
            'dimension_id' => $volume->id,
            'preferred_unit_id' => $inch->id,
        ]);
    }

    public function test_factory_creates_matching_dimension_and_preferred_unit(): void
    {
        $preference = MarketUnitPreference::factory()->create();

        $this->assertSame($preference->dimension_id, $preference->preferredUnit->dimension_id);
    }
}
