<?php

namespace App\Support;

use App\Enums\UserRole;
use InvalidArgumentException;

class PermissionMatrix
{
    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return config('cataloghub_permissions.permissions', []);
    }

    public function allows(UserRole $role, string $permission): bool
    {
        $this->ensureKnownPermission($permission);

        $rolePermissions = config("cataloghub_permissions.roles.{$role->value}", []);

        return in_array('*', $rolePermissions, true)
            || in_array($permission, $rolePermissions, true);
    }

    private function ensureKnownPermission(string $permission): void
    {
        if (! in_array($permission, $this->permissions(), true)) {
            throw new InvalidArgumentException("Unknown CatalogHub permission [{$permission}].");
        }
    }
}
