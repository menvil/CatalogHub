<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use LogicException;

class FoundationRolesSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = config('cataloghub_permissions.roles', []);
        $expectedRoles = array_map(
            static fn (UserRole $role): string => $role->value,
            UserRole::cases(),
        );

        if (array_keys($definitions) !== $expectedRoles) {
            throw new LogicException('Foundation role definitions must match the UserRole registry.');
        }

        foreach ($definitions as $role => $permissions) {
            if ($permissions !== array_values(array_unique($permissions))) {
                throw new LogicException("Foundation role [{$role}] contains duplicate permissions.");
            }

            if (in_array('*', $permissions, true) && $permissions !== ['*']) {
                throw new LogicException("Foundation role [{$role}] must not mix wildcard and named permissions.");
            }

            foreach ($permissions as $permission) {
                if ($permission !== '*' && Permission::tryFrom($permission) === null) {
                    throw new LogicException("Foundation role [{$role}] references unknown permission [{$permission}].");
                }
            }
        }
    }
}
