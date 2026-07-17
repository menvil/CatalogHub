<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

final class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasCatalogHubPermission('reviews.moderate');
    }

    public function view(User $user, Review $review): bool
    {
        return $this->viewAny($user)
            && ($user->isSuperAdmin()
                || ($user->site_id !== null && (int) $user->site_id === (int) $review->site_id));
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Review $review): bool
    {
        return $this->view($user, $review);
    }
}
