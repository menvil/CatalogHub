<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

final class Login extends BaseLogin
{
    protected string $view = 'auth.central-login';

    public function getHeading(): string | Htmlable | null
    {
        return 'Sign in to Central Admin';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Authorized platform operators only.';
    }
}
