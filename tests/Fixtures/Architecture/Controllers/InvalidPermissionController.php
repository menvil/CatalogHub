<?php

namespace App\Http\Controllers\ArchitectureFixtures;

use App\Http\Controllers\Controller;
use App\Models\User;

final class InvalidPermissionController extends Controller
{
    public function __invoke(User $user): bool
    {
        return $user->hasCatalogHubPermission('media.manage');
    }
}
