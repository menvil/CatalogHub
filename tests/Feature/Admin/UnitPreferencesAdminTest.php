<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Resources\AttributeDisplayRuleResource;
use App\Filament\Resources\AttributeDisplayRuleResource\Pages\CreateAttributeDisplayRule;
use App\Filament\Resources\AttributeDisplayRuleResource\Pages\EditAttributeDisplayRule;
use App\Filament\Resources\AttributeDisplayRuleResource\Pages\ListAttributeDisplayRules;
use App\Filament\Resources\MarketUnitPreferenceResource;
use App\Filament\Resources\MarketUnitPreferenceResource\Pages\CreateMarketUnitPreference;
use App\Filament\Resources\MarketUnitPreferenceResource\Pages\EditMarketUnitPreference;
use App\Filament\Resources\MarketUnitPreferenceResource\Pages\ListMarketUnitPreferences;
use App\Models\AttributeDisplayRule;
use App\Models\MarketUnitPreference;
use App\Models\MeasurementUnit;
use App\Models\User;
use Database\Seeders\ImperialMeasurementUnitsSeeder;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitPreferencesAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_preference_resources_exist_with_pages(): void
    {
        $this->assertSame(MarketUnitPreference::class, MarketUnitPreferenceResource::getModel());
        $this->assertSame(AttributeDisplayRule::class, AttributeDisplayRuleResource::getModel());
        $this->assertTrue(class_exists(ListMarketUnitPreferences::class));
        $this->assertTrue(class_exists(CreateMarketUnitPreference::class));
        $this->assertTrue(class_exists(EditMarketUnitPreference::class));
        $this->assertTrue(class_exists(ListAttributeDisplayRules::class));
        $this->assertTrue(class_exists(CreateAttributeDisplayRule::class));
        $this->assertTrue(class_exists(EditAttributeDisplayRule::class));
    }

    public function test_admin_access_and_preview_formatting(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class, ImperialMeasurementUnitsSeeder::class]);

        $centralAdmin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $normalUser = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $inch = MeasurementUnit::query()->where('code', 'inch')->firstOrFail();

        $this->actingAs($centralAdmin);
        $this->assertTrue(MarketUnitPreferenceResource::canAccess());
        $this->assertSame('27 "', MarketUnitPreferenceResource::previewCanonicalValue(685.8, $inch));

        $this->actingAs($normalUser);
        $this->assertFalse(MarketUnitPreferenceResource::canAccess());
    }
}
