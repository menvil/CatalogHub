<?php

namespace Tests\Feature\Actions;

use App\Actions\Reviews\RejectReviewAction;
use App\Enums\ReviewStatus;
use App\Exceptions\Reviews\CannotModerateReviewException;
use App\Models\Review;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RejectReviewActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_admin_can_reject_a_pending_review_for_their_site(): void
    {
        $site = Site::factory()->create();
        $admin = User::factory()->siteAdmin($site)->create();
        $review = Review::factory()->pending()->create(['site_id' => $site->id]);

        $rejected = app(RejectReviewAction::class)->handle($admin, $review, 'Spammy wording.');

        $this->assertSame(ReviewStatus::Rejected, $rejected->status);
        $this->assertSame('Spammy wording.', $rejected->rejection_reason);
        $this->assertNotNull($rejected->rejected_at);
        $this->assertNull($rejected->approved_at);
        $this->assertFalse(Review::query()->visiblePublicly()->whereKey($rejected)->exists());
    }

    public function test_site_admin_cannot_reject_another_sites_review(): void
    {
        $admin = User::factory()->siteAdmin(Site::factory()->create())->create();
        $review = Review::factory()->pending()->create();

        $this->expectException(CannotModerateReviewException::class);

        app(RejectReviewAction::class)->handle($admin, $review, 'Not acceptable.');
    }

    public function test_rejection_reason_is_required(): void
    {
        $site = Site::factory()->create();
        $admin = User::factory()->siteAdmin($site)->create();
        $review = Review::factory()->pending()->create(['site_id' => $site->id]);

        try {
            app(RejectReviewAction::class)->handle($admin, $review, '   ');
            $this->fail('A rejection reason should be required.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reason', $exception->errors());
        }

        $this->assertSame(ReviewStatus::Pending, $review->fresh()->status);
    }
}
