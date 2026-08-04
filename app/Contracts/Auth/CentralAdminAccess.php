<?php

declare(strict_types=1);

namespace App\Contracts\Auth;

use App\Models\User;

interface CentralAdminAccess
{
    public function allows(User $user): bool;
}
