<?php

namespace App\Actions\Corrections;

use App\Enums\ChangeRequestStatus;
use App\Exceptions\Corrections\CannotReviewCorrectionException;
use App\Models\ChangeRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class ApproveCorrectionAction
{
    public function handle(User $admin, ChangeRequest $request): ChangeRequest
    {
        if (! $admin->hasCatalogHubPermission('corrections.review')) {
            throw new AuthorizationException('Only a central administrator can approve corrections.');
        }

        return DB::transaction(function () use ($admin, $request): ChangeRequest {
            $lockedRequest = ChangeRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== ChangeRequestStatus::Pending) {
                throw CannotReviewCorrectionException::because('Only a pending correction can be approved.');
            }

            $lockedRequest->forceFill([
                'status' => ChangeRequestStatus::Approved,
                'reviewed_by_user_id' => $admin->getKey(),
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ])->save();

            return $lockedRequest;
        });
    }
}
