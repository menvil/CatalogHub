<?php

namespace Tests\Feature\Actions\Pricing;

use App\Actions\Pricing\RejectExternalProductMappingAction;
use App\Enums\ExternalProductMappingStatus;
use App\Enums\UserRole;
use App\Exceptions\Pricing\CannotRejectExternalProductMappingException;
use App\Filament\Resources\ExternalProductMappingResource\Pages\ListExternalProductMappings;
use App\Models\ExternalProductMapping;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RejectExternalProductMappingActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_admin_can_reject_pending_mapping_with_reason(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $mapping = ExternalProductMapping::factory()->pending()->create();

        app(RejectExternalProductMappingAction::class)->handle(
            admin: $admin,
            mapping: $mapping,
            reason: 'Wrong product match.',
        );

        $mapping->refresh();
        $this->assertSame(ExternalProductMappingStatus::Rejected, $mapping->status);
        $this->assertSame($admin->id, $mapping->rejected_by_user_id);
        $this->assertNotNull($mapping->rejected_at);
        $this->assertStringContainsString('Wrong product match.', (string) $mapping->notes);
        $this->assertSame('rejected', $mapping->metadata['last_mapping_action']['action']);
    }

    public function test_approved_mapping_cannot_be_rejected_in_mvp(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $mapping = ExternalProductMapping::factory()->approved()->create();

        $this->expectException(CannotRejectExternalProductMappingException::class);
        app(RejectExternalProductMappingAction::class)->handle($admin, $mapping, 'Wrong product.');
    }

    public function test_user_without_price_permission_cannot_reject_mapping(): void
    {
        $user = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $mapping = ExternalProductMapping::factory()->pending()->create();

        $this->expectException(AuthorizationException::class);
        app(RejectExternalProductMappingAction::class)->handle($user, $mapping, 'Wrong product.');
    }

    public function test_filament_reject_action_uses_backend_transition(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $mapping = ExternalProductMapping::factory()->pending()->create();

        Livewire::actingAs($admin)
            ->test(ListExternalProductMappings::class)
            ->callTableAction('reject', $mapping, data: ['reason' => 'Not the same model.'])
            ->assertHasNoActionErrors();

        $this->assertSame(ExternalProductMappingStatus::Rejected, $mapping->fresh()->status);
    }
}
