<?php

namespace Tests\Feature\Auth;

use App\Enums\Permission;
use App\Enums\UserRole;
use Database\Seeders\FoundationRolesSeeder;
use Tests\TestCase;

class FoundationRolesSeederTest extends TestCase
{
    public function test_six_foundation_roles_have_only_registered_permissions(): void
    {
        $definitions = config('cataloghub_permissions.roles');
        $roles = UserRole::cases();

        $this->assertCount(6, $roles);
        $this->assertCount(6, $definitions);

        $this->assertSame(
            array_map(static fn (UserRole $role): string => $role->value, $roles),
            array_keys($definitions),
        );

        foreach ($definitions as $permissions) {
            foreach ($permissions as $permission) {
                $this->assertTrue(
                    $permission === '*' || Permission::tryFrom($permission) !== null,
                    "Unregistered permission [{$permission}] is assigned to a foundation role.",
                );
            }
        }
    }

    public function test_foundation_role_seed_is_idempotent(): void
    {
        $before = config('cataloghub_permissions.roles');

        $this->seed(FoundationRolesSeeder::class);
        $this->seed(FoundationRolesSeeder::class);

        $this->assertSame($before, config('cataloghub_permissions.roles'));
    }
}
