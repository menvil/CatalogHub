<?php

namespace Tests\Feature\Services;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use App\Services\ProductAttributes\CanonicalValuePreviewer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanonicalValuePreviewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_converts_numeric_value_to_canonical_unit(): void
    {
        $dimension = MeasurementDimension::factory()->create(['code' => 'volume']);
        MeasurementUnit::factory()->for($dimension, 'dimension')->create([
            'code' => 'liter',
            'symbol' => 'l',
            'factor_to_canonical' => '1',
            'precision_default' => 3,
            'is_canonical' => true,
        ]);
        MeasurementUnit::factory()->for($dimension, 'dimension')->create([
            'code' => 'gallon_us',
            'symbol' => 'gal',
            'factor_to_canonical' => '3.785411784',
            'precision_default' => 3,
        ]);
        $attribute = AttributeDefinition::factory()->create([
            'data_type' => 'decimal',
            'dimension' => 'volume',
            'canonical_unit' => 'liter',
        ]);

        $preview = app(CanonicalValuePreviewer::class)->preview($attribute, [
            'value_number' => 1.3,
            'source_unit' => 'gallon_us',
        ]);

        $this->assertNotNull($preview);
        $this->assertSame('liter', $preview['unit']);
        $this->assertSame('4.921 l', $preview['label']);
    }

    public function test_returns_null_for_non_numeric_attribute(): void
    {
        $attribute = AttributeDefinition::factory()->create(['data_type' => 'string']);

        $this->assertNull(app(CanonicalValuePreviewer::class)->preview($attribute, [
            'value_text' => 'LG',
        ]));
    }

    public function test_returns_null_for_empty_numeric_value(): void
    {
        $attribute = AttributeDefinition::factory()->create(['data_type' => 'decimal']);

        $this->assertNull(app(CanonicalValuePreviewer::class)->preview($attribute, [
            'value_number' => '',
        ]));
    }

    public function test_returns_warning_with_source_unit_when_conversion_fails(): void
    {
        $volume = MeasurementDimension::factory()->create(['code' => 'volume']);
        $mass = MeasurementDimension::factory()->create(['code' => 'mass']);
        MeasurementUnit::factory()->for($volume, 'dimension')->create([
            'code' => 'liter',
            'symbol' => 'l',
            'factor_to_canonical' => '1',
        ]);
        MeasurementUnit::factory()->for($mass, 'dimension')->create([
            'code' => 'pound',
            'symbol' => 'lb',
            'factor_to_canonical' => '0.45359237',
        ]);
        $attribute = AttributeDefinition::factory()->create([
            'data_type' => 'decimal',
            'dimension' => 'volume',
            'canonical_unit' => 'liter',
        ]);

        $preview = app(CanonicalValuePreviewer::class)->preview($attribute, [
            'value_number' => 2.2,
            'source_unit' => 'pound',
        ]);

        $this->assertNotNull($preview);
        $this->assertSame('pound', $preview['unit']);
        $this->assertSame('2.2 lb', $preview['label']);
        $this->assertNotNull($preview['warning']);
    }
}
