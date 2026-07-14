<?php

namespace Tests\Feature\Filament;

use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Filament\Resources\ReviewResource;
use App\Filament\Resources\ReviewResource\Pages\ListReviews;
use App\Models\Review;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewsModerationScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_admin_sees_reviews_for_their_site_only(): void
    {
        $site = Site::factory()->create();
        $otherSite = Site::factory()->create();
        $ownReview = Review::factory()->pending()->create(['site_id' => $site->id]);
        $otherReview = Review::factory()->pending()->create(['site_id' => $otherSite->id]);
        $admin = User::factory()->siteAdmin($site)->create();

        $this->actingAs($admin)
            ->get(ReviewResource::getUrl())
            ->assertOk()
            ->assertSee($ownReview->author_name)
            ->assertDontSee($otherReview->author_name);

        Livewire::actingAs($admin)
            ->test(ListReviews::class)
            ->assertCanSeeTableRecords([$ownReview])
            ->assertCanNotSeeTableRecords([$otherReview]);
    }

    public function test_super_admin_can_see_reviews_for_all_sites(): void
    {
        $first = Review::factory()->pending()->create();
        $second = Review::factory()->approved()->create();
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        Livewire::actingAs($admin)
            ->test(ListReviews::class)
            ->assertCanSeeTableRecords([$first, $second]);
    }

    public function test_user_without_moderation_permission_cannot_open_reviews_resource(): void
    {
        $user = User::factory()->create(['role' => UserRole::CatalogEditor]);

        $this->actingAs($user)
            ->get(ReviewResource::getUrl())
            ->assertForbidden();
    }

    public function test_reviews_resource_is_read_only_and_has_status_filter(): void
    {
        $this->assertSame(['index'], array_keys(ReviewResource::getPages()));

        $site = Site::factory()->create();
        $pending = Review::factory()->pending()->create(['site_id' => $site->id]);
        $approved = Review::factory()->approved()->create(['site_id' => $site->id]);

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(ListReviews::class)
            ->filterTable('status', 'pending')
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$approved]);
    }

    public function test_site_admin_can_approve_pending_review_from_table(): void
    {
        $site = Site::factory()->create();
        $review = Review::factory()->pending()->create(['site_id' => $site->id]);

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(ListReviews::class)
            ->callTableAction('approve', $review)
            ->assertHasNoActionErrors();

        $this->assertSame(ReviewStatus::Approved, $review->fresh()->status);
    }

    public function test_site_admin_can_reject_pending_review_from_table(): void
    {
        $site = Site::factory()->create();
        $review = Review::factory()->pending()->create(['site_id' => $site->id]);

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(ListReviews::class)
            ->callTableAction('reject', $review, data: ['reason' => 'Does not meet review guidelines.'])
            ->assertHasNoActionErrors();

        $this->assertSame(ReviewStatus::Rejected, $review->fresh()->status);
        $this->assertSame('Does not meet review guidelines.', $review->fresh()->rejection_reason);
    }

    public function test_site_admin_can_mark_review_as_spam_from_table(): void
    {
        $site = Site::factory()->create();
        $review = Review::factory()->pending()->create(['site_id' => $site->id]);

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(ListReviews::class)
            ->callTableAction('markSpam', $review)
            ->assertHasNoActionErrors();

        $this->assertSame(ReviewStatus::Spam, $review->fresh()->status);
        $this->assertNotNull($review->fresh()->spam_marked_at);
    }
}
