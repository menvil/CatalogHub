<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

final class Login extends BaseLogin
{
    protected string $view = 'auth.central-login';

    public function getHeading(): string
    {
        return 'Sign in to Central Admin';
    }

    public function getSubheading(): string
    {
        return 'Authorized platform operators only.';
    }
}
