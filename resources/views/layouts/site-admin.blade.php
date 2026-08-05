@php
    $siteAdminNavigation ??= [
        ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'url' => null],
        ['id' => 'site-settings', 'label' => 'Site Settings', 'icon' => 'cog-6-tooth', 'url' => null],
        ['id' => 'categories', 'label' => 'Categories', 'icon' => 'squares-2x2', 'url' => null],
        ['id' => 'products', 'label' => 'Products', 'icon' => 'archive-box', 'url' => null],
        ['id' => 'theme', 'label' => 'Theme', 'icon' => 'pencil-square', 'url' => null],
        ['id' => 'blocks', 'label' => 'Blocks', 'icon' => 'squares-2x2', 'url' => null],
        ['id' => 'sync', 'label' => 'Sync', 'icon' => 'arrow-up-tray', 'url' => null],
        ['id' => 'prices', 'label' => 'Prices', 'icon' => 'currency-dollar', 'url' => null],
        ['id' => 'reviews', 'label' => 'Reviews', 'icon' => 'inbox-stack', 'url' => null],
        ['id' => 'leads', 'label' => 'Leads', 'icon' => 'users', 'url' => null],
        ['id' => 'content', 'label' => 'Content', 'icon' => 'language', 'url' => null],
        ['id' => 'polls', 'label' => 'Polls', 'icon' => 'information-circle', 'url' => null],
        ['id' => 'settings', 'label' => 'Settings', 'icon' => 'cog-6-tooth', 'url' => null],
    ];
    $siteAdminUser ??= auth()->user();
    $siteAdminCurrentSite ??= request()->attributes->get('site_context');
    $siteAdminRuntimeContext ??= request()->attributes->get(\App\Support\Sites\SiteRuntimeContext::class);
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
    <body class="min-h-screen overflow-x-hidden bg-admin-background font-sans text-admin-text antialiased">
        <a href="#site-main-content" class="sr-only focus:not-sr-only">Skip to main content</a>

        <div
            class="min-h-screen lg:flex"
            data-admin-layout="site"
            data-site-shell
            data-presentation-context="site-admin"
        >
            <x-site-admin.sidebar
                :items="$siteAdminNavigation"
                :active-nav="$activeNav ?? null"
                data-site-sidebar
            />

            <div class="min-w-0 flex-1">
                @include('site-admin.components.header', ['siteAdminUser' => $siteAdminUser])

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

                        <div class="flex flex-col gap-admin-field md:flex-row md:items-start md:justify-between">
                            <nav class="min-w-0 text-sm text-admin-muted" aria-label="Breadcrumbs">
                                @yield('breadcrumbs')
                            </nav>

                            <div class="flex flex-wrap items-center gap-admin-field">
                                @yield('pageActions')
                            </div>
                        </div>

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
