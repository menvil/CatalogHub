<?php

declare(strict_types=1);

namespace App\Support\DesignSystem;

use App\Enums\UserRole;
use App\Models\User;

final class CentralShellFixture
{
    public const VERSION = 'central-shell-v1';

    public const STATES = ['default', 'collapsed', 'mobile', 'long-header'];

    public static function user(): User
    {
        return new User([
            'name' => 'Central Acceptance User',
            'email' => 'central.acceptance@example.test',
            'role' => UserRole::CentralAdmin,
        ]);
    }

    private function __construct() {}
}
