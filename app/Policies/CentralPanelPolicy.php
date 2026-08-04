<?php

declare(strict_types=1);

namespace App\Policies;

use App\Contracts\Auth\CentralAdminAccess;
use App\Enums\Permission;
use App\Models\User;
use App\Services\Auth\AuthorizationService;

final readonly class CentralPanelPolicy implements CentralAdminAccess
{
    public function __construct(private AuthorizationService $authorization) {}

    public function allows(User $user): bool
    {
        return $this->authorization->allowsPanel($user, Permission::CentralPanelAccess);
    }
}
