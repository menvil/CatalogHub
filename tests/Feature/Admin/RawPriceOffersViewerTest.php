<?php

namespace Tests\Feature\Admin;

use App\Enums\RawPriceOfferStatus;
use App\Enums\UserRole;
use App\Filament\Resources\RawPriceOfferResource;
use App\Filament\Resources\RawPriceOfferResource\Pages\ListRawPriceOffers;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\RawPriceOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RawPriceOffersViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_admin_can_filter_and_inspect_raw_and_normalized_payloads(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $source = PriceSource::factory()->create(['name' => 'Manual Offers DE']);
        $log = PriceSourceSyncLog::factory()->for($source)->create();
        $visible = RawPriceOffer::factory()->normalized()->for($source)->create([
            'price_source_sync_log_id' => $log->id,
            'external_sku' => 'K2',
            'raw_payload_json' => ['sku' => 'K2', 'price' => 79.99],
            'normalized_payload_json' => ['external_sku' => 'K2', 'price' => '79.99', 'currency' => 'EUR'],
            'fetched_at' => now(),
        ]);
        $hidden = RawPriceOffer::factory()->for(PriceSource::factory())->create([
            'status' => RawPriceOfferStatus::Failed,
            'fetched_at' => now()->subWeek(),
        ]);

        $this->actingAs($admin)
            ->get(RawPriceOfferResource::getUrl())
            ->assertOk()
            ->assertSee('Manual Offers DE')
            ->assertSee('K2');

        Livewire::actingAs($admin)
            ->test(ListRawPriceOffers::class)
            ->filterTable('price_source_id', $source->id)
            ->filterTable('status', RawPriceOfferStatus::Normalized->value)
            ->filterTable('fetched_at', ['from' => now()->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$hidden]);

        $this->actingAs($admin)
            ->get(RawPriceOfferResource::getUrl('view', ['record' => $visible]))
            ->assertOk()
            ->assertSee('79.99')
            ->assertSee('EUR')
            ->assertSee((string) $log->id);
    }

    public function test_raw_offer_resource_is_read_only_and_forbidden_without_price_permission(): void
    {
        $this->assertSame(['index', 'view'], array_keys(RawPriceOfferResource::getPages()));
        $user = User::factory()->create(['role' => UserRole::CatalogEditor]);

        $this->actingAs($user)
            ->get(RawPriceOfferResource::getUrl())
            ->assertForbidden();
    }
}
