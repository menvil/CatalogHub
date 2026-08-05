<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\Permission;
use App\Enums\SiteMembershipRole;
use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Auth\AuthorizationService;
use Illuminate\Support\Facades\DB;

final readonly class UpsertSiteMembershipAction
{
    public function __construct(
        private AuthorizationService $authorization,
        private AuditRecorder $audit,
    ) {}

    public function handle(
        User $actor,
        User $member,
        Site $site,
        SiteMembershipRole $role,
        bool $isActive,
    ): SiteMembership {
        return DB::transaction(function () use ($actor, $member, $site, $role, $isActive): SiteMembership {
            $this->authorization->authorizeMutation($actor, Permission::SiteMutationExecute, $site);
            $site = Site::query()
                ->whereKey($site->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $membership = SiteMembership::query()
                ->whereBelongsTo($member)
                ->whereBelongsTo($site)
                ->lockForUpdate()
                ->first();
            $before = $membership instanceof SiteMembership ? [
                'role' => $membership->roleEnum()->value,
                'is_active' => $membership->is_active,
            ] : null;

            if ($membership instanceof SiteMembership) {
                $membership->update(['role' => $role, 'is_active' => $isActive]);
            } else {
                $membership = SiteMembership::query()->create([
                    'user_id' => $member->getKey(),
                    'site_id' => $site->getKey(),
                    'role' => $role,
                    'is_active' => $isActive,
                ]);
            }
            $this->audit->record(
                AuditAction::MembershipChanged,
                AuditContext::Site,
                $actor,
                $membership,
                $site,
                $before,
                ['role' => $role->value, 'is_active' => $isActive],
            );

            return $membership;
        });
    }
}
