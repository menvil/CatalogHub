<?php

namespace Tests\Feature\Actions\Pricing;

use App\Actions\Pricing\ManualRemapExternalProductAction;
use App\Enums\CentralProductStatus;
use App\Enums\ExternalProductMappingStatus;
use App\Enums\MarketOfferStatus;
use App\Enums\UserRole;
use App\Exceptions\Pricing\CannotRemapExternalProductException;
use App\Filament\Resources\ExternalProductMappingResource\Pages\ListExternalProductMappings;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ExternalProductMapping;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManualRemapExternalProductActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_admin_can_remap_and_mark_related_offers_stale(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $oldProduct = CentralProduct::factory()->create();
        $newProduct = CentralProduct::factory()->create(['status' => CentralProductStatus::Active]);
        $mapping = ExternalProductMapping::factory()->approved()->create([
            'central_product_id' => $oldProduct->id,
        ]);
        $merchant = MarketMerchant::factory()->create(['market_id' => $mapping->priceSource->market_id]);
        $offer = MarketOffer::factory()->create([
            'market_id' => $mapping->priceSource->market_id,
            'market_merchant_id' => $merchant->id,
            'central_product_id' => $oldProduct->id,
            'price_source_id' => $mapping->price_source_id,
            'external_product_mapping_id' => $mapping->id,
        ]);

        app(ManualRemapExternalProductAction::class)->handle(
            admin: $admin,
            mapping: $mapping,
            newCentralProduct: $newProduct,
            reason: 'Incorrect initial match.',
        );

        $mapping->refresh();
        $offer->refresh();
        $this->assertSame($newProduct->id, $mapping->central_product_id);
        $this->assertSame(ExternalProductMappingStatus::Approved, $mapping->status);
        $this->assertSame('1.0000', $mapping->confidence);
        $this->assertStringContainsString('Incorrect initial match.', (string) $mapping->notes);
        $this->assertSame('manual_remap', $mapping->metadata['last_mapping_action']['action']);
        $this->assertSame($newProduct->id, $offer->central_product_id);
        $this->assertSame(MarketOfferStatus::Stale, $offer->status);
    }

    public function test_archived_product_and_same_product_are_invalid_remap_targets(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $mapping = ExternalProductMapping::factory()->approved()->create();
        $archived = CentralProduct::factory()->create(['status' => CentralProductStatus::Archived]);

        try {
            app(ManualRemapExternalProductAction::class)->handle($admin, $mapping, $archived, 'Wrong match.');
            $this->fail('Expected archived target rejection.');
        } catch (CannotRemapExternalProductException) {
            $this->assertNotSame($archived->id, $mapping->fresh()->central_product_id);
        }

        $this->expectException(CannotRemapExternalProductException::class);
        app(ManualRemapExternalProductAction::class)->handle(
            $admin,
            $mapping,
            $mapping->centralProduct,
            'No actual change.',
        );
    }

    public function test_remap_handles_an_existing_target_offer_without_violating_current_offer_uniqueness(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $oldProduct = CentralProduct::factory()->create();
        $newProduct = CentralProduct::factory()->create();
        $mapping = ExternalProductMapping::factory()->approved()->create([
            'central_product_id' => $oldProduct->id,
        ]);
        $merchant = MarketMerchant::factory()->create(['market_id' => $mapping->priceSource->market_id]);
        $oldOffer = MarketOffer::factory()->create([
            'market_id' => $mapping->priceSource->market_id,
            'market_merchant_id' => $merchant->id,
            'central_product_id' => $oldProduct->id,
            'price_source_id' => $mapping->price_source_id,
            'external_product_mapping_id' => $mapping->id,
        ]);
        $targetOffer = MarketOffer::factory()->create([
            'market_id' => $mapping->priceSource->market_id,
            'market_merchant_id' => $merchant->id,
            'central_product_id' => $newProduct->id,
            'price_source_id' => $mapping->price_source_id,
            'external_product_mapping_id' => null,
        ]);

        app(ManualRemapExternalProductAction::class)->handle(
            $admin,
            $mapping,
            $newProduct,
            'Use the existing current offer.',
        );

        $this->assertNull($oldOffer->fresh()->external_product_mapping_id);
        $this->assertSame(MarketOfferStatus::Stale, $oldOffer->fresh()->status);
        $this->assertSame($mapping->id, $targetOffer->fresh()->external_product_mapping_id);
        $this->assertSame(MarketOfferStatus::Stale, $targetOffer->fresh()->status);
    }

    public function test_user_without_price_permission_cannot_remap(): void
    {
        $user = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $mapping = ExternalProductMapping::factory()->approved()->create();

        $this->expectException(AuthorizationException::class);
        app(ManualRemapExternalProductAction::class)->handle(
            $user,
            $mapping,
            CentralProduct::factory()->create(),
            'Wrong match.',
        );
    }

    public function test_filament_manual_remap_action_uses_backend_transition(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $mapping = ExternalProductMapping::factory()->approved()->create();
        $newProduct = CentralProduct::factory()->create();

        Livewire::actingAs($admin)
            ->test(ListExternalProductMappings::class)
            ->callTableAction('manualRemap', $mapping, data: [
                'central_product_id' => $newProduct->id,
                'reason' => 'Selected the wrong product.',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame($newProduct->id, $mapping->fresh()->central_product_id);
        $this->assertSame(ExternalProductMappingStatus::Approved, $mapping->fresh()->status);
    }
}
