<?php

namespace Tests\Feature\Actions;

use App\Actions\Corrections\ApproveCorrectionAction;
use App\Enums\ChangeRequestStatus;
use App\Exceptions\Corrections\CannotReviewCorrectionException;
use App\Models\ChangeRequest;
use App\Models\Site;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApproveCorrectionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_can_approve_a_pending_correction(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $request = ChangeRequest::factory()->pending()->create();
        $originalName = $request->centralProduct->name;
        $originalVersion = $request->centralProduct->version;

        $approved = app(ApproveCorrectionAction::class)->handle($admin, $request);

        $this->assertSame(ChangeRequestStatus::Approved, $approved->status);
        $this->assertTrue($approved->reviewedBy->is($admin));
        $this->assertNotNull($approved->reviewed_at);
        $this->assertSame($originalName, $request->centralProduct->fresh()->name);
        $this->assertSame($originalVersion, $request->centralProduct->fresh()->version);
        $this->assertDatabaseCount('central_product_versions', 0);
    }

    public function test_non_pending_correction_cannot_be_approved(): void
    {
        $this->expectException(CannotReviewCorrectionException::class);

        app(ApproveCorrectionAction::class)->handle(
            User::factory()->centralAdmin()->create(),
            ChangeRequest::factory()->rejected()->create(),
        );
    }

    public function test_site_admin_cannot_approve_a_correction(): void
    {
        $site = Site::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(ApproveCorrectionAction::class)->handle(
            User::factory()->siteAdmin($site)->create(),
            ChangeRequest::factory()->pending()->create(),
        );
    }
}
