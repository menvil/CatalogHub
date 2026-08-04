<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Auth\AuthorizationService;
use Illuminate\Support\Facades\DB;

final readonly class AssignUserRoleAction
{
    public function __construct(
        private AuthorizationService $authorization,
        private AuditRecorder $audit,
    ) {}

    public function handle(User $actor, User $subject, UserRole $role): User
    {
        return DB::transaction(function () use ($actor, $subject, $role): User {
            $this->authorization->authorizeMutation($actor, Permission::CentralMutationExecute);
            $before = ['role' => $subject->roleEnum()->value];
            $subject->update(['role' => $role]);
            $this->audit->record(
                AuditAction::RoleAssigned,
                AuditContext::Central,
                $actor,
                $subject,
                null,
                $before,
                ['role' => $role->value],
            );

            return $subject->refresh();
        });
    }
}
