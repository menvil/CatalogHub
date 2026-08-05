@php
    $siteAdminUser ??= auth()->user();
    $siteAdminCurrentSite ??= request()->attributes->get('site_context');
    $siteAdminRuntimeContext ??= request()->attributes->get(\App\Support\Sites\SiteRuntimeContext::class);
    $siteAdminNavigation ??= $siteAdminUser instanceof \App\Models\User && $siteAdminCurrentSite instanceof \App\Models\Site
        ? app(\App\Navigation\SiteAdminNavigationRegistry::class)->visibleItemsFor($siteAdminUser, $siteAdminCurrentSite)
        : [];
    $siteSidebarInitiallyOpen = ($siteAdminShellPreviewState ?? null) === 'mobile';
    $documentTitle = trim($__env->yieldContent('pageTitle', $pageTitle ?? $title ?? 'Site Admin'));
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? $documentTitle }} - {{ config('app.name', 'CatalogHub') }}</title>

        @stack('head')
        @vite(['resources/css/site-admin.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body @class([
        'min-h-screen overflow-x-hidden bg-admin-background font-sans text-admin-text antialiased',
        'site-sidebar-scroll-locked' => $siteSidebarInitiallyOpen,
    ])>
        <a href="#site-main-content" class="sr-only focus:not-sr-only">Skip to main content</a>

        <div
            class="min-h-screen lg:flex"
            data-admin-layout="site"
            data-site-shell
            data-site-sidebar-collapsed="false"
            data-site-sidebar-mobile-open="{{ $siteSidebarInitiallyOpen ? 'true' : 'false' }}"
            data-site-sidebar-persist="{{ ($acceptance ?? false) ? 'false' : 'true' }}"
            @if (isset($siteAdminShellPreviewState)) data-site-preview-state="{{ $siteAdminShellPreviewState }}" @endif
            data-presentation-context="site-admin"
        >
            <x-site-admin.sidebar
                :items="$siteAdminNavigation"
                :active-nav="$activeNav ?? null"
                :current-site="$siteAdminCurrentSite"
                :mobile-open="$siteSidebarInitiallyOpen"
                data-site-sidebar
            />

            <div class="min-w-0 flex-1">
                @include('site-admin.components.header', [
                    'siteAdminUser' => $siteAdminUser,
                    'siteSidebarInitiallyOpen' => $siteSidebarInitiallyOpen,
                ])

                <main id="site-main-content" class="px-admin-page py-admin-section" tabindex="-1">
                    <div class="mx-auto max-w-7xl space-y-admin-section">
                        @if ($siteAdminCurrentSite instanceof \App\Models\Site && $siteAdminUser instanceof \App\Models\User)
                            <x-site-admin.site-selector
                                :current-site="$siteAdminCurrentSite"
                                :user="$siteAdminUser"
                                :sites="$siteAdminAuthorizedSites ?? null"
                            />
                        @endif

                        @if ($siteAdminRuntimeContext instanceof \App\Support\Sites\SiteRuntimeContext)
                            <x-site-admin.site-context-header :context="$siteAdminRuntimeContext" />
                        @else
                            <x-admin.site-context-switcher
                                :site-label="$siteLabel ?? 'Demo portal'"
                                :market-label="$marketLabel ?? 'Default market'"
                                :locale-label="$localeLabel ?? 'en'"
                            />
                        @endif

                        <section aria-label="Site Admin content">
                            {{ $slot ?? '' }}
                            @yield('content')
                        </section>
                    </div>
                </main>
            </div>
        </div>

        @livewireScripts
        @stack('scripts')
    </body>
</html>
