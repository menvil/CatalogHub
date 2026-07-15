<?php

namespace Tests\Feature\Actions\Pricing;

use App\Actions\Pricing\ApproveExternalProductMappingAction;
use App\Enums\ExternalProductMappingStatus;
use App\Enums\UserRole;
use App\Exceptions\Pricing\CannotApproveExternalProductMappingException;
use App\Filament\Resources\ExternalProductMappingResource\Pages\ListExternalProductMappings;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ExternalProductMapping;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApproveExternalProductMappingActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_admin_can_approve_pending_mapping_with_central_product(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $mapping = ExternalProductMapping::factory()->pending()->create([
            'central_product_id' => CentralProduct::factory(),
        ]);

        app(ApproveExternalProductMappingAction::class)->handle($admin, $mapping);

        $mapping->refresh();
        $this->assertSame(ExternalProductMappingStatus::Approved, $mapping->status);
        $this->assertSame($admin->id, $mapping->approved_by_user_id);
        $this->assertNotNull($mapping->approved_at);
        $this->assertSame('approved', $mapping->metadata['last_mapping_action']['action']);
    }

    public function test_mapping_without_product_or_invalid_transition_cannot_be_approved(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $mapping = ExternalProductMapping::factory()->pending()->create();

        try {
            app(ApproveExternalProductMappingAction::class)->handle($admin, $mapping);
            $this->fail('Expected missing central product rejection.');
        } catch (CannotApproveExternalProductMappingException) {
            $this->assertSame(ExternalProductMappingStatus::Pending, $mapping->fresh()->status);
        }

        $rejected = ExternalProductMapping::factory()->rejected()->create([
            'central_product_id' => CentralProduct::factory(),
        ]);

        $this->expectException(CannotApproveExternalProductMappingException::class);
        app(ApproveExternalProductMappingAction::class)->handle($admin, $rejected);
    }

    public function test_user_without_price_permission_cannot_approve_mapping(): void
    {
        $user = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $mapping = ExternalProductMapping::factory()->pending()->create([
            'central_product_id' => CentralProduct::factory(),
        ]);

        $this->expectException(AuthorizationException::class);
        app(ApproveExternalProductMappingAction::class)->handle($user, $mapping);
    }

    public function test_filament_approve_action_uses_backend_transition(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $mapping = ExternalProductMapping::factory()->pending()->create([
            'central_product_id' => CentralProduct::factory(),
        ]);

        Livewire::actingAs($admin)
            ->test(ListExternalProductMappings::class)
            ->callTableAction('approve', $mapping)
            ->assertHasNoActionErrors();

        $this->assertSame(ExternalProductMappingStatus::Approved, $mapping->fresh()->status);
    }
}
