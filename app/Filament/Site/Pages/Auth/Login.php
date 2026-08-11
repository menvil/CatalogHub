<?php

declare(strict_types=1);

namespace App\Filament\Site\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

final class Login extends BaseLogin
{
    protected string $view = 'auth.site-admin-login';

    public function getHeading(): string
    {
        return 'Sign in to Site Admin';
    }

    public function getSubheading(): string
    {
        return 'Your authorized sites appear after sign-in.';
    }
}
