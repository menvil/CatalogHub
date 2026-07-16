<?php

namespace Tests\Feature\Actions;

use App\Actions\Corrections\RejectCorrectionAction;
use App\Enums\ChangeRequestStatus;
use App\Exceptions\Corrections\CannotReviewCorrectionException;
use App\Models\ChangeRequest;
use App\Models\Site;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RejectCorrectionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_can_reject_a_pending_correction_with_a_reason(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $request = ChangeRequest::factory()->pending()->create();

        $rejected = app(RejectCorrectionAction::class)->handle(
            admin: $admin,
            request: $request,
            reason: '  Evidence is not reliable.  ',
        );

        $this->assertSame(ChangeRequestStatus::Rejected, $rejected->status);
        $this->assertSame('Evidence is not reliable.', $rejected->rejection_reason);
        $this->assertTrue($rejected->reviewedBy->is($admin));
        $this->assertNotNull($rejected->reviewed_at);
    }

    public function test_rejection_reason_is_required(): void
    {
        $this->expectException(ValidationException::class);

        app(RejectCorrectionAction::class)->handle(
            User::factory()->centralAdmin()->create(),
            ChangeRequest::factory()->pending()->create(),
            '   ',
        );
    }

    public function test_approved_correction_cannot_be_rejected(): void
    {
        $this->expectException(CannotReviewCorrectionException::class);

        app(RejectCorrectionAction::class)->handle(
            User::factory()->centralAdmin()->create(),
            ChangeRequest::factory()->approved()->create(),
            'Too late.',
        );
    }

    public function test_site_admin_cannot_reject_a_correction(): void
    {
        $site = Site::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(RejectCorrectionAction::class)->handle(
            User::factory()->siteAdmin($site)->create(),
            ChangeRequest::factory()->pending()->create(),
            'Not allowed.',
        );
    }
}
