<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\Permission;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Auth\AuthorizationService;
use Illuminate\Support\Facades\DB;

final readonly class SetUserDisabledAction
{
    public function __construct(
        private AuthorizationService $authorization,
        private AuditRecorder $audit,
    ) {}

    public function handle(User $actor, User $subject, bool $disabled): User
    {
        return DB::transaction(function () use ($actor, $subject, $disabled): User {
            $this->authorization->authorizeMutation($actor, Permission::CentralMutationExecute);
            $before = ['is_disabled' => ! $subject->isActive()];
            $subject->update(['disabled_at' => $disabled ? now() : null]);
            $this->audit->record(
                $disabled ? AuditAction::UserDisabled : AuditAction::UserEnabled,
                AuditContext::Central,
                $actor,
                $subject,
                null,
                $before,
                ['is_disabled' => $disabled],
            );

            return $subject->refresh();
        });
    }
}
