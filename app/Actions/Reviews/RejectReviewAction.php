<?php

namespace App\Actions\Reviews;

use App\Actions\Reviews\Concerns\AuthorizesReviewModeration;
use App\Enums\ReviewStatus;
use App\Exceptions\Reviews\CannotModerateReviewException;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class RejectReviewAction
{
    use AuthorizesReviewModeration;

    public function handle(User $user, Review $review, string $reason): Review
    {
        $data = Validator::make(['reason' => trim($reason)], [
            'reason' => ['required', 'string', 'max:2000'],
        ])->validate();

        return DB::transaction(function () use ($data, $review, $user): Review {
            $lockedReview = Review::query()->lockForUpdate()->findOrFail($review->getKey());

            $this->authorize($user, $lockedReview);

            if ($lockedReview->status !== ReviewStatus::Pending) {
                throw CannotModerateReviewException::because('Only pending reviews can be rejected.');
            }

            $lockedReview->forceFill([
                'status' => ReviewStatus::Rejected,
                'approved_at' => null,
                'rejected_at' => now(),
                'rejection_reason' => $data['reason'],
                'spam_marked_at' => null,
            ])->save();

            return $lockedReview->refresh();
        });
    }
}
