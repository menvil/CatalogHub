<?php

namespace App\Actions\Imports;

use App\Models\Imports\DuplicateCandidate;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;

final class ReviewDuplicateCandidateAction
{
    public function handle(DuplicateCandidate $candidate, ?User $user, string $decision): DuplicateCandidate
    {
        if (! $user instanceof User || ! ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->isCatalogEditor())) {
            throw new AuthorizationException('You are not allowed to review duplicate candidates.');
        }

        if (! in_array($decision, ['confirmed_duplicate', 'not_duplicate'], true)) {
            throw new InvalidArgumentException("Unsupported duplicate review decision [{$decision}].");
        }

        $candidate->forceFill([
            'status' => $decision,
            'reviewed_by_user_id' => $user->id,
            'reviewed_at' => now(),
        ])->save();

        return $candidate->refresh();
    }
}
