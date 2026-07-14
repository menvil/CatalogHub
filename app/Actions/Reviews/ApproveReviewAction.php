<?php

namespace App\Actions\Reviews;

use App\Enums\ReviewStatus;
use App\Exceptions\Reviews\CannotModerateReviewException;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ApproveReviewAction
{
    public function handle(User $user, Review $review): Review
    {
        return DB::transaction(function () use ($review, $user): Review {
            $lockedReview = Review::query()->lockForUpdate()->findOrFail($review->getKey());

            $this->authorize($user, $lockedReview);

            if ($lockedReview->status !== ReviewStatus::Pending) {
                throw CannotModerateReviewException::because('Only pending reviews can be approved.');
            }

            $lockedReview->forceFill([
                'status' => ReviewStatus::Approved,
                'approved_at' => now(),
                'rejected_at' => null,
                'spam_marked_at' => null,
            ])->save();

            return $lockedReview->refresh();
        });
    }

    private function authorize(User $user, Review $review): void
    {
        $canModerate = $user->hasCatalogHubPermission('reviews.moderate')
            && ($user->isSuperAdmin() || (int) $user->site_id === (int) $review->site_id);

        if (! $canModerate) {
            throw CannotModerateReviewException::because('You cannot moderate reviews for this site.');
        }
    }
}
