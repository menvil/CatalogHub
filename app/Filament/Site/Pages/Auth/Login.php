<?php

declare(strict_types=1);

namespace App\Filament\Site\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

final class Login extends BaseLogin
{
    protected string $view = 'auth.site-admin-login';

    public function getHeading(): string | Htmlable | null
    {
        return 'Sign in to Site Admin';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Your authorized sites appear after sign-in.';
    }
}
