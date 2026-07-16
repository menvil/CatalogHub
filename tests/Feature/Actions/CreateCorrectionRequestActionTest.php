<?php

namespace Tests\Feature\Actions;

use App\Actions\Corrections\CreateCorrectionRequestAction;
use App\Enums\ChangeRequestStatus;
use App\Exceptions\Corrections\CannotCreateCorrectionException;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCorrectionRequestActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_admin_can_create_a_pending_correction_without_mutating_central(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create(['name' => 'Old title']);
        SiteProduct::factory()->for($site)->for($product, 'centralProduct')->create();
        $admin = User::factory()->siteAdmin($site)->create();

        $request = app(CreateCorrectionRequestAction::class)->handle(
            creator: $admin,
            product: $product,
            fieldPath: 'name',
            proposedValue: 'New title',
            evidenceUrl: 'https://manufacturer.example/product',
            evidenceNote: 'The official product page shows the corrected title.',
        );

        $this->assertSame(ChangeRequestStatus::Pending, $request->status);
        $this->assertSame(['value' => 'Old title'], $request->old_value_json);
        $this->assertSame(['value' => 'New title'], $request->proposed_value_json);
        $this->assertTrue($request->createdBy->is($admin));
        $this->assertSame('Old title', $product->fresh()->name);
        $this->assertSame(1, $product->fresh()->version);
    }

    public function test_site_admin_cannot_request_a_correction_for_another_site_product(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        SiteProduct::factory()->for(Site::factory())->for($product, 'centralProduct')->create();

        $this->expectException(CannotCreateCorrectionException::class);

        app(CreateCorrectionRequestAction::class)->handle(
            User::factory()->siteAdmin($site)->create(),
            $product,
            'name',
            'Correction',
        );
    }

    public function test_non_site_user_cannot_create_a_correction_request(): void
    {
        $this->expectException(AuthorizationException::class);

        app(CreateCorrectionRequestAction::class)->handle(
            User::factory()->centralAdmin()->create(),
            CentralProduct::factory()->create(),
            'name',
            'Correction',
        );
    }
}
