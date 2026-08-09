<?php

namespace App\Providers\Filament;

use App\Filament\Site\Pages\Home;
use App\Filament\Site\Pages\Auth\Login;
use App\Http\Middleware\EnsureSiteAdminAccess;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RequireSiteContext;
use App\Http\Middleware\ResolveSiteRuntimeContext;
use App\Models\Site;
use App\Models\User;
use App\Navigation\SiteAdminNavigationRegistry;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class SiteAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('site')
            ->path('admin/site')
            ->viteTheme('resources/css/site-admin.css')
            ->brandName('CatalogHub Site Admin')
            ->login(Login::class)
            ->navigation(fn (SiteAdminNavigationRegistry $registry) => $registry->filamentNavigation(
                auth()->user() instanceof User ? auth()->user() : null,
                request()->attributes->get('site_context') instanceof Site
                    ? request()->attributes->get('site_context')
                    : null,
            ))
            ->colors([
                'primary' => Color::Blue,
            ])
            ->pages([
                Home::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                EnsureUserIsActive::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureSiteAdminAccess::class,
                RequireSiteContext::class,
                ResolveSiteRuntimeContext::class,
            ]);
    }
}
