<?php

namespace Tests\Feature\Admin;

use App\Enums\ExternalProductMappingStatus;
use App\Enums\UserRole;
use App\Filament\Resources\ExternalProductMappingResource;
use App\Filament\Resources\ExternalProductMappingResource\Pages\ListExternalProductMappings;
use App\Models\ExternalProductMapping;
use App\Models\PriceSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExternalProductMappingAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_admin_can_list_filter_and_view_mapping_details(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $source = PriceSource::factory()->create(['name' => 'Idealo DE']);
        $visible = ExternalProductMapping::factory()->approved()->for($source)->create([
            'external_title' => 'LG 27GP850-B.AEU',
            'external_sku' => '27GP850-B.AEU',
            'confidence' => 0.86,
        ]);
        $hidden = ExternalProductMapping::factory()->rejected()->create(['confidence' => 0.20]);

        $this->actingAs($admin)
            ->get(ExternalProductMappingResource::getUrl())
            ->assertOk()
            ->assertSee('LG 27GP850-B.AEU')
            ->assertSee('Idealo DE');

        Livewire::actingAs($admin)
            ->test(ListExternalProductMappings::class)
            ->filterTable('price_source_id', $source->id)
            ->filterTable('market_id', $source->market_id)
            ->filterTable('status', ExternalProductMappingStatus::Approved->value)
            ->filterTable('confidence', ['min' => 0.8, 'max' => 0.9])
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$hidden]);

        $this->actingAs($admin)
            ->get(ExternalProductMappingResource::getUrl('view', ['record' => $visible]))
            ->assertOk()
            ->assertSee('27GP850-B.AEU')
            ->assertSee($visible->centralProduct->name);
    }

    public function test_mapping_resource_is_read_only_and_forbidden_without_price_permission(): void
    {
        $this->assertSame(['index', 'view'], array_keys(ExternalProductMappingResource::getPages()));
        $user = User::factory()->create(['role' => UserRole::CatalogEditor]);

        $this->actingAs($user)
            ->get(ExternalProductMappingResource::getUrl())
            ->assertForbidden();
    }
}
