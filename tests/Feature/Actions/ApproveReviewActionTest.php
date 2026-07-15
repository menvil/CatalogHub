<?php

namespace Tests\Feature\Actions;

use App\Actions\Reviews\ApproveReviewAction;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Exceptions\Reviews\CannotModerateReviewException;
use App\Models\Review;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApproveReviewActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_admin_can_approve_a_pending_review_for_their_site(): void
    {
        $site = Site::factory()->create();
        $admin = User::factory()->siteAdmin($site)->create();
        $review = Review::factory()->pending()->create([
            'site_id' => $site->id,
            'rejected_at' => now(),
            'rejection_reason' => 'Stale rejection reason.',
            'spam_marked_at' => now(),
        ]);

        $approved = app(ApproveReviewAction::class)->handle($admin, $review);

        $this->assertSame(ReviewStatus::Approved, $approved->status);
        $this->assertNotNull($approved->approved_at);
        $this->assertNull($approved->rejected_at);
        $this->assertNull($approved->rejection_reason);
        $this->assertNull($approved->spam_marked_at);
    }

    public function test_super_admin_can_approve_a_review_for_any_site(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $review = Review::factory()->pending()->create();

        $approved = app(ApproveReviewAction::class)->handle($admin, $review);

        $this->assertSame(ReviewStatus::Approved, $approved->status);
    }

    public function test_site_admin_cannot_approve_another_sites_review(): void
    {
        $admin = User::factory()->siteAdmin(Site::factory()->create())->create();
        $review = Review::factory()->pending()->create();

        $this->expectException(CannotModerateReviewException::class);

        app(ApproveReviewAction::class)->handle($admin, $review);
    }

    public function test_only_pending_reviews_can_be_approved(): void
    {
        $site = Site::factory()->create();
        $admin = User::factory()->siteAdmin($site)->create();
        $review = Review::factory()->rejected()->create(['site_id' => $site->id]);

        try {
            app(ApproveReviewAction::class)->handle($admin, $review);
            $this->fail('A rejected review should not be approved.');
        } catch (CannotModerateReviewException) {
            $this->assertSame(ReviewStatus::Rejected, $review->fresh()->status);
        }
    }
}
