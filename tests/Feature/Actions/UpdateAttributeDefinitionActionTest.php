<?php

namespace Tests\Feature\Actions;

use App\Actions\CategorySchema\UpdateAttributeDefinitionAction;
use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\MeasurementUnit;
use App\Models\User;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateAttributeDefinitionActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->centralAdmin()->create());
    }

    public function test_updates_global_canonical_definition_without_local_ownership(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class]);
        $attribute = AttributeDefinition::factory()->create(['code' => 'refresh_rate', 'name' => 'Old name']);
        $unit = MeasurementUnit::query()->where('code', 'hertz')->sole();
        app(UpdateAttributeDefinitionAction::class)->handle($attribute, ['name' => 'Refresh rate', 'code' => 'refresh_rate',
            'data_type' => 'integer', 'measurement_dimension_id' => $unit->dimension_id, 'canonical_measurement_unit_id' => $unit->id]);
        $attribute->refresh();
        self::assertSame('refresh_rate', $attribute->code);
        self::assertSame('Refresh rate', $attribute->name);
        self::assertSame(AttributeDataType::Integer, $attribute->data_type);
        self::assertSame($unit->id, $attribute->canonical_measurement_unit_id);
        self::assertArrayNotHasKey('position', $attribute->getAttributes());
    }

    public function test_allows_keeping_current_attribute_code(): void
    {
        $attribute = AttributeDefinition::factory()->create(['code' => 'refresh_rate']);

        app(UpdateAttributeDefinitionAction::class)->handle($attribute, [
            'name' => 'Refresh rate',
            'code' => 'refresh_rate',
            'data_type' => AttributeDataType::Integer->value,
        ]);

        $this->assertSame('Refresh rate', $attribute->fresh()->name);
    }

    public function test_rejects_canonical_code_changes(): void
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        AttributeDefinition::factory()->assignedTo($category)->state(['attribute_section_id' => $section->id])->create(['code' => 'weight']);
        $attribute = AttributeDefinition::factory()->assignedTo($category)->state(['attribute_section_id' => $section->id])->create(['code' => 'height']);

        $this->expectException(ValidationException::class);

        app(UpdateAttributeDefinitionAction::class)->handle($attribute, [
            'name' => 'Height',
            'code' => 'weight',
            'data_type' => AttributeDataType::Decimal->value,
        ]);
    }

    public function test_does_not_change_enum_attribute_with_options_to_non_option_type(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'code' => 'panel_type',
            'data_type' => AttributeDataType::Enum,
        ]);
        AttributeOption::factory()->for($attribute, 'attribute')->create();

        $this->expectException(ValidationException::class);

        app(UpdateAttributeDefinitionAction::class)->handle($attribute, [
            'name' => 'Panel type',
            'code' => 'panel_type',
            'data_type' => AttributeDataType::String->value,
        ]);
    }
}
