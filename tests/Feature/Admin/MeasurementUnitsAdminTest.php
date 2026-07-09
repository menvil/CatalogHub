<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Resources\MeasurementDimensionResource;
use App\Filament\Resources\MeasurementDimensionResource\Pages\CreateMeasurementDimension;
use App\Filament\Resources\MeasurementDimensionResource\Pages\EditMeasurementDimension;
use App\Filament\Resources\MeasurementDimensionResource\Pages\ListMeasurementDimensions;
use App\Filament\Resources\MeasurementUnitResource;
use App\Filament\Resources\MeasurementUnitResource\Pages\CreateMeasurementUnit;
use App\Filament\Resources\MeasurementUnitResource\Pages\EditMeasurementUnit;
use App\Filament\Resources\MeasurementUnitResource\Pages\ListMeasurementUnits;
use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementUnitsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_units_resources_exist_with_pages(): void
    {
        $this->assertSame(MeasurementDimension::class, MeasurementDimensionResource::getModel());
        $this->assertSame(MeasurementUnit::class, MeasurementUnitResource::getModel());
        $this->assertTrue(class_exists(ListMeasurementDimensions::class));
        $this->assertTrue(class_exists(CreateMeasurementDimension::class));
        $this->assertTrue(class_exists(EditMeasurementDimension::class));
        $this->assertTrue(class_exists(ListMeasurementUnits::class));
        $this->assertTrue(class_exists(CreateMeasurementUnit::class));
        $this->assertTrue(class_exists(EditMeasurementUnit::class));
    }

    public function test_only_central_admins_can_access_units_resources(): void
    {
        $centralAdmin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $normalUser = User::factory()->create(['role' => UserRole::CatalogEditor]);

        $this->actingAs($centralAdmin);
        $this->assertTrue(MeasurementUnitResource::canAccess());

        $this->actingAs($normalUser);
        $this->assertFalse(MeasurementUnitResource::canAccess());
    }
}
