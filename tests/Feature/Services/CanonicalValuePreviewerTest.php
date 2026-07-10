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
}
