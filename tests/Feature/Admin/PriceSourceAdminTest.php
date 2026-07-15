<?php

namespace Tests\Feature\Admin;

use App\Enums\PriceSourceStatus;
use App\Enums\PriceSourceType;
use App\Enums\PriceSourceUpdateFrequency;
use App\Enums\UserRole;
use App\Filament\Resources\PriceSourceResource;
use App\Filament\Resources\PriceSourceResource\Pages\CreatePriceSource;
use App\Filament\Resources\PriceSourceResource\Pages\ListPriceSources;
use App\Jobs\Pricing\FetchExternalOffersJob;
use App\Models\Market;
use App\Models\PriceSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PriceSourceAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_admin_can_list_and_create_sources(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $market = Market::factory()->create();
        $existing = PriceSource::factory()->for($market)->create(['name' => 'Existing Feed']);

        $this->actingAs($admin)
            ->get(PriceSourceResource::getUrl())
            ->assertOk()
            ->assertSee($existing->name);

        Livewire::actingAs($admin)
            ->test(CreatePriceSource::class)
            ->fillForm([
                'market_id' => $market->id,
                'code' => 'manual-de',
                'name' => 'Manual DE',
                'type' => PriceSourceType::Manual->value,
                'status' => PriceSourceStatus::Active->value,
                'update_frequency' => PriceSourceUpdateFrequency::Manual->value,
                'config_json' => ['default_currency' => 'EUR'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('price_sources', ['market_id' => $market->id, 'code' => 'manual-de']);
    }

    public function test_admin_can_store_masked_credentials_and_trigger_sync(): void
    {
        Bus::fake();
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $source = PriceSource::factory()->active()->create();

        Livewire::actingAs($admin)
            ->test(ListPriceSources::class)
            ->callTableAction('credentials', $source, data: [
                'credentials' => ['api_key' => 'secret-key'],
            ])
            ->assertHasNoActionErrors()
            ->callTableAction('triggerSync', $source)
            ->assertHasNoActionErrors();

        $raw = DB::table('price_source_credentials')->value('encrypted_credentials_json');
        $this->assertIsString($raw);
        $this->assertStringNotContainsString('secret-key', $raw);
        $this->assertArrayNotHasKey('encrypted_credentials_json', $source->credentials()->firstOrFail()->toArray());
        Bus::assertDispatched(FetchExternalOffersJob::class);
    }

    public function test_user_without_price_permission_cannot_manage_sources(): void
    {
        $user = User::factory()->create(['role' => UserRole::CatalogEditor]);

        $this->actingAs($user)
            ->get(PriceSourceResource::getUrl())
            ->assertForbidden();
    }
}
