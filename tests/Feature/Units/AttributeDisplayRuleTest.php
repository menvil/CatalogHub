<?php

namespace Tests\Feature\Units;

use App\Models\AttributeDisplayRule;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\MeasurementUnit;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttributeDisplayRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_model_and_relations_work(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        $this->assertTrue(Schema::hasTable('attribute_display_rules'));
        $this->assertTrue(Schema::hasColumns('attribute_display_rules', [
            'id',
            'attribute_definition_id',
            'market_code',
            'locale',
            'display_unit_id',
            'decimals',
            'rounding_mode',
            'suffix_style',
            'created_at',
            'updated_at',
        ]));

        $attribute = AttributeDefinition::factory()->create([
            'code' => 'diagonal',
            'name' => 'Diagonal',
        ]);
        $inch = MeasurementUnit::query()->where('code', 'inch')->firstOrFail();

        $rule = AttributeDisplayRule::create([
            'attribute_definition_id' => $attribute->id,
            'market_code' => 'US',
            'locale' => 'en_US',
            'display_unit_id' => $inch->id,
            'decimals' => 1,
            'rounding_mode' => 'half_up',
            'suffix_style' => 'symbol',
        ]);

        $this->assertTrue($rule->attributeDefinition->is($attribute));
        $this->assertTrue($rule->displayUnit->is($inch));
        $this->assertSame(1, $rule->decimals);
    }

    public function test_attribute_display_rule_scope_is_unique_when_market_and_locale_are_set(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        $attribute = AttributeDefinition::factory()->create();
        $inch = MeasurementUnit::query()->where('code', 'inch')->firstOrFail();

        AttributeDisplayRule::create([
            'attribute_definition_id' => $attribute->id,
            'market_code' => 'US',
            'locale' => 'en_US',
            'display_unit_id' => $inch->id,
            'rounding_mode' => 'half_up',
            'suffix_style' => 'symbol',
        ]);

        $this->expectException(QueryException::class);

        AttributeDisplayRule::create([
            'attribute_definition_id' => $attribute->id,
            'market_code' => 'US',
            'locale' => 'en_US',
            'display_unit_id' => $inch->id,
            'rounding_mode' => 'half_up',
            'suffix_style' => 'symbol',
        ]);
    }
}
