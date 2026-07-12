<?php

namespace Tests\Feature\Admin;

use App\Actions\Translations\SaveUnitTranslationAction;
use App\Models\Locale;
use App\Models\MeasurementUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class UnitTranslationEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_central_admin_to_save_unit_translation(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $unit = MeasurementUnit::factory()->create(['code' => 'watt']);
        $locale = Locale::factory()->create(['code' => 'ru-RU']);

        $this->actingAs($admin)
            ->post(route('central.units.translations.save', [$unit, $locale]), [
                'short_name' => 'Вт',
                'long_name' => 'ватт',
                'plural_name' => 'ватт',
                'symbol_position' => 'after',
                'space_between_value_and_unit' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('unit_translations', [
            'measurement_unit_id' => $unit->id,
            'locale' => 'ru-RU',
            'short_name' => 'Вт',
            'long_name' => 'ватт',
            'plural_name' => 'ватт',
            'symbol_position' => 'after',
            'space_between_value_and_unit' => true,
        ]);
        $this->assertDatabaseHas('measurement_units', [
            'id' => $unit->id,
            'code' => 'watt',
        ]);
    }

    public function test_rejects_invalid_unit_symbol_position(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $unit = MeasurementUnit::factory()->create(['code' => 'watt']);
        $locale = Locale::factory()->create(['code' => 'ru-RU']);

        $this->actingAs($admin)
            ->from(route('central.units.translations.edit', [$unit, $locale]))
            ->post(route('central.units.translations.save', [$unit, $locale]), [
                'short_name' => 'Вт',
                'symbol_position' => 'middle',
            ])
            ->assertRedirect(route('central.units.translations.edit', [$unit, $locale]))
            ->assertSessionHasErrors('symbol_position');
    }

    public function test_save_unit_translation_action_rejects_invalid_symbol_position(): void
    {
        $unit = MeasurementUnit::factory()->create(['code' => 'watt']);
        $locale = Locale::factory()->create(['code' => 'ru-RU']);

        $this->expectException(InvalidArgumentException::class);

        app(SaveUnitTranslationAction::class)->handle($unit, $locale, [
            'short_name' => 'Вт',
            'symbol_position' => 'middle',
        ]);
    }
}
