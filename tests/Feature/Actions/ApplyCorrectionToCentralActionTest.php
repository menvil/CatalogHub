<?php

namespace Tests\Feature\Actions;

use App\Actions\Corrections\ApplyCorrectionToCentralAction;
use App\Enums\ChangeRequestStatus;
use App\Exceptions\Corrections\CannotApplyCorrectionException;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ChangeRequest;
use App\Models\Site;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApplyCorrectionToCentralActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_approved_correction_versions_product_and_writes_sync_log(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $product = CentralProduct::factory()->create(['name' => 'Old title', 'version' => 1]);
        $request = ChangeRequest::factory()->approved()->create([
            'central_product_id' => $product->id,
            'entity_id' => $product->id,
            'field_path' => 'name',
            'old_value_json' => ['value' => 'Old title'],
            'proposed_value_json' => ['value' => 'New title'],
        ]);

        $applied = app(ApplyCorrectionToCentralAction::class)->handle($admin, $request);

        $this->assertSame('New title', $product->fresh()->name);
        $this->assertSame(2, $product->fresh()->version);
        $this->assertSame(ChangeRequestStatus::Applied, $applied->status);
        $this->assertTrue($applied->appliedBy->is($admin));
        $this->assertNotNull($applied->applied_at);
        $this->assertDatabaseHas('central_product_versions', [
            'central_product_id' => $product->id,
            'version' => 2,
            'changed_by_user_id' => $admin->id,
            'change_type' => 'correction',
        ]);
        $this->assertDatabaseHas('sync_logs', [
            'site_id' => $request->site_id,
            'central_product_id' => $product->id,
            'operation' => 'apply_correction',
            'status' => 'completed',
            'triggered_by' => 'correction',
            'affected_count' => 1,
        ]);
    }

    #[DataProvider('nonApplicableStates')]
    public function test_only_approved_correction_can_be_applied(string $factoryState): void
    {
        $request = ChangeRequest::factory()->{$factoryState}()->create();

        $this->expectException(CannotApplyCorrectionException::class);

        app(ApplyCorrectionToCentralAction::class)->handle(
            User::factory()->centralAdmin()->create(),
            $request,
        );
    }

    public function test_unsupported_field_rolls_back_the_whole_apply_workflow(): void
    {
        $product = CentralProduct::factory()->create(['version' => 1]);
        $request = ChangeRequest::factory()->approved()->create([
            'central_product_id' => $product->id,
            'field_path' => 'updated_at',
            'proposed_value_json' => ['value' => 'unsafe'],
        ]);

        try {
            app(ApplyCorrectionToCentralAction::class)->handle(
                User::factory()->centralAdmin()->create(),
                $request,
            );
            $this->fail('An unsupported canonical field was applied.');
        } catch (CannotApplyCorrectionException) {
            $this->assertSame(ChangeRequestStatus::Approved, $request->fresh()->status);
            $this->assertSame(1, $product->fresh()->version);
            $this->assertDatabaseCount('central_product_versions', 0);
            $this->assertDatabaseCount('sync_logs', 0);
        }
    }

    public function test_site_admin_cannot_apply_a_correction(): void
    {
        $site = Site::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(ApplyCorrectionToCentralAction::class)->handle(
            User::factory()->siteAdmin($site)->create(),
            ChangeRequest::factory()->approved()->create(),
        );
    }

    /** @return array<string, array{string}> */
    public static function nonApplicableStates(): array
    {
        return [
            'pending' => ['pending'],
            'rejected' => ['rejected'],
            'applied' => ['applied'],
        ];
    }
}
