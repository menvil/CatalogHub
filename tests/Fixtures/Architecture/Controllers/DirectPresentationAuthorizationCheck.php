<?php

namespace App\Http\Controllers\ArchitectureFixtures;

use App\Models\User;

final class DirectControllerAuthorizationCheck
{
    public function check(User $user): bool
    {
        return $user->hasCatalogHubPermission('media.manage');
    }
}

namespace App\Filament\ArchitectureFixtures;

use App\Models\User;

final class DirectFilamentAuthorizationCheck
{
    public function check(?User $user): bool
    {
        return $user?->isSuperAdmin() === true;
    }
}

namespace App\Http\Requests\ArchitectureFixtures;

use App\Models\User;

final class DirectRequestAuthorizationCheck
{
    public function check(User $user): mixed
    {
        return $user->role;
    }
}

namespace App\Livewire\ArchitectureFixtures;

use App\Models\User;
use App\Support\PermissionMatrix;

final class DirectLivewireAuthorizationCheck
{
    public function check(User $user, PermissionMatrix $permissions): array
    {
        return [
            $user->canManageImports(),
            $user->getAttribute('role'),
            $user['role'],
            $permissions->allows($user->role, 'imports.manage'),
        ];
    }

    public function valid(User $user, AuthorizationLookalike $lookalike): array
    {
        return [
            $user->can('imports.manage'),
            $lookalike->isSuperAdmin(),
        ];
    }
}

final class AuthorizationLookalike
{
    public function isSuperAdmin(): bool
    {
        return false;
    }
}
