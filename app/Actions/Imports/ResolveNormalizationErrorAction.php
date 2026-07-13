<?php

namespace App\Actions\Imports;

use App\Models\Imports\NormalizationError;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class ResolveNormalizationErrorAction
{
    public function handle(NormalizationError $error, ?User $user): NormalizationError
    {
        if (! $user instanceof User || ! ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->isCatalogEditor())) {
            throw new AuthorizationException('You are not allowed to resolve normalization errors.');
        }

        return DB::transaction(function () use ($error, $user): NormalizationError {
            $lockedError = NormalizationError::query()
                ->lockForUpdate()
                ->findOrFail($error->getKey());

            if ($lockedError->resolved_at !== null) {
                return $lockedError;
            }

            $lockedError->forceFill([
                'resolved_at' => now(),
                'resolved_by_user_id' => $user->id,
            ])->save();

            return $lockedError->refresh();
        });
    }
}
