<?php

namespace App\Services\Categories;

use App\Enums\Permission;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class CategoryAccess
{
    public function __construct(private AuthorizationService $authorization) {}

    public function allows(Permission $permission, bool $mutation = false, ?User $actor = null): bool
    {
        $actor ??= auth()->user();

        return $actor instanceof User
            && $actor->isActive()
            && $this->authorization->allowsPanel($actor, Permission::CentralPanelAccess)
            && $actor->hasCatalogHubPermission(Permission::CentralPageAccess->value)
            && $actor->hasCatalogHubPermission($permission->value)
            && (! $mutation || $actor->hasCatalogHubPermission(Permission::CentralMutationExecute->value));
    }

    public function authorize(Permission $permission, ?User $actor = null): User
    {
        $actor ??= auth()->user();
        if (! $actor instanceof User || ! $this->allows($permission, true, $actor)) {
            throw new AuthorizationException;
        }

        $this->authorization->authorizeMutation($actor, Permission::CentralMutationExecute);

        return $actor;
    }
}
