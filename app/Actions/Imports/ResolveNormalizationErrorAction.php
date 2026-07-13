<?php

namespace App\Actions\Imports;

use App\Models\Imports\NormalizationError;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class ResolveNormalizationErrorAction
{
    public function handle(NormalizationError $error, ?User $user): NormalizationError
    {
        if (! $user instanceof User || ! ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->isCatalogEditor())) {
            throw new AuthorizationException('You are not allowed to resolve normalization errors.');
        }

        $error->forceFill([
            'resolved_at' => now(),
            'resolved_by_user_id' => $user->id,
        ])->save();

        return $error->refresh();
    }
}
