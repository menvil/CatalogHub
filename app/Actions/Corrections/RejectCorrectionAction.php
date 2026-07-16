<?php

namespace App\Actions\Corrections;

use App\Enums\ChangeRequestStatus;
use App\Exceptions\Corrections\CannotReviewCorrectionException;
use App\Models\ChangeRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class RejectCorrectionAction
{
    public function handle(User $admin, ChangeRequest $request, string $reason): ChangeRequest
    {
        if (! $admin->hasCatalogHubPermission('corrections.review')) {
            throw new AuthorizationException('Only a central administrator can reject corrections.');
        }

        $validated = Validator::make(
            ['reason' => trim($reason)],
            ['reason' => ['required', 'string', 'max:5000']],
        )->validate();

        return DB::transaction(function () use ($admin, $request, $validated): ChangeRequest {
            $lockedRequest = ChangeRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== ChangeRequestStatus::Pending) {
                throw CannotReviewCorrectionException::because('Only a pending correction can be rejected.');
            }

            $lockedRequest->forceFill([
                'status' => ChangeRequestStatus::Rejected,
                'reviewed_by_user_id' => $admin->getKey(),
                'reviewed_at' => now(),
                'rejection_reason' => $validated['reason'],
            ])->save();

            return $lockedRequest;
        });
    }
}
