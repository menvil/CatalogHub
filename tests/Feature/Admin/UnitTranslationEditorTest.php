<?php

namespace Tests\Feature\Admin;

use App\Models\Locale;
use App\Models\MeasurementUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                'space_between_value_and_unit' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('unit_translations', [
            'measurement_unit_id' => $unit->id,
            'locale' => 'ru-RU',
            'short_name' => 'Вт',
        ]);
        $this->assertDatabaseHas('measurement_units', [
            'id' => $unit->id,
            'code' => 'watt',
        ]);
    }
}
