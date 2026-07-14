<?php

namespace App\Actions\Reviews\Concerns;

use App\Exceptions\Reviews\CannotModerateReviewException;
use App\Models\Review;
use App\Models\User;

trait AuthorizesReviewModeration
{
    private function authorize(User $user, Review $review): void
    {
        $canModerate = $user->hasCatalogHubPermission('reviews.moderate')
            && ($user->isSuperAdmin() || (int) $user->site_id === (int) $review->site_id);

        if (! $canModerate) {
            throw CannotModerateReviewException::because('You cannot moderate reviews for this site.');
        }
    }
}
