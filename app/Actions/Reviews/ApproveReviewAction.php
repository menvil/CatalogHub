<?php

namespace App\Actions\Reviews;

use App\Actions\Reviews\Concerns\AuthorizesReviewModeration;
use App\Enums\ReviewStatus;
use App\Exceptions\Reviews\CannotModerateReviewException;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ApproveReviewAction
{
    use AuthorizesReviewModeration;

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
                'rejection_reason' => null,
                'spam_marked_at' => null,
            ])->save();

            return $lockedReview->refresh();
        });
    }
}
