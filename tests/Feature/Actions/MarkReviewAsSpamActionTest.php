<?php

namespace Tests\Feature\Actions;

use App\Actions\Reviews\MarkReviewAsSpamAction;
use App\Enums\ReviewStatus;
use App\Exceptions\Reviews\CannotModerateReviewException;
use App\Models\Review;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkReviewAsSpamActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_admin_can_mark_a_review_as_spam(): void
    {
        $site = Site::factory()->create();
        $admin = User::factory()->siteAdmin($site)->create();
        $review = Review::factory()->pending()->create(['site_id' => $site->id]);

        $spam = app(MarkReviewAsSpamAction::class)->handle($admin, $review);

        $this->assertSame(ReviewStatus::Spam, $spam->status);
        $this->assertNotNull($spam->spam_marked_at);
        $this->assertNull($spam->approved_at);
        $this->assertNull($spam->rejected_at);
        $this->assertFalse(Review::query()->visiblePublicly()->whereKey($spam)->exists());
    }

    public function test_an_approved_review_can_be_removed_as_spam(): void
    {
        $site = Site::factory()->create();
        $admin = User::factory()->siteAdmin($site)->create();
        $review = Review::factory()->approved()->create(['site_id' => $site->id]);

        $spam = app(MarkReviewAsSpamAction::class)->handle($admin, $review);

        $this->assertSame(ReviewStatus::Spam, $spam->status);
        $this->assertNull($spam->approved_at);
    }

    public function test_site_admin_cannot_mark_another_sites_review_as_spam(): void
    {
        $admin = User::factory()->siteAdmin(Site::factory()->create())->create();
        $review = Review::factory()->pending()->create();

        $this->expectException(CannotModerateReviewException::class);

        app(MarkReviewAsSpamAction::class)->handle($admin, $review);
    }
}
