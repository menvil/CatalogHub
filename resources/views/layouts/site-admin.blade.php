@php
    $siteAdminNavigation = [
        ['key' => 'dashboard', 'label' => 'Dashboard'],
        ['key' => 'site-settings', 'label' => 'Site Settings'],
        ['key' => 'categories', 'label' => 'Categories'],
        ['key' => 'products', 'label' => 'Products'],
        ['key' => 'theme', 'label' => 'Theme'],
        ['key' => 'blocks', 'label' => 'Blocks'],
        ['key' => 'sync', 'label' => 'Sync'],
        ['key' => 'prices', 'label' => 'Prices'],
        ['key' => 'reviews', 'label' => 'Reviews'],
        ['key' => 'leads', 'label' => 'Leads'],
        ['key' => 'content', 'label' => 'Content'],
        ['key' => 'polls', 'label' => 'Polls'],
        ['key' => 'settings', 'label' => 'Settings'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? 'Site Admin' }} - {{ config('app.name', 'CatalogHub') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-admin-background font-sans text-admin-text antialiased">
        <div class="min-h-screen lg:flex" data-admin-layout="site">
            <x-admin.sidebar
                context="site"
                :items="$siteAdminNavigation"
                :active-nav="$activeNav ?? null"
            />

            <div class="min-w-0 flex-1">
                <x-admin.topbar context-label="Site Admin" search-placeholder="Search site workspace">
                    <x-slot:title>
                        <h1 class="text-xl font-semibold text-admin-text">
                            @yield('pageTitle', $pageTitle ?? 'Site Admin')
                        </h1>
                    </x-slot:title>
                </x-admin.topbar>

                <main class="px-admin-page py-admin-section">
                    <div class="mx-auto max-w-7xl space-y-admin-section">
                        <x-admin.site-context-switcher
                            :site-label="$siteLabel ?? 'Demo portal'"
                            :market-label="$marketLabel ?? 'Default market'"
                            :locale-label="$localeLabel ?? 'en'"
                        />

                        <div class="flex flex-col gap-admin-field md:flex-row md:items-start md:justify-between">
                            <div class="min-w-0">
                                <nav class="text-sm text-admin-muted" aria-label="Breadcrumbs">
                                    @yield('breadcrumbs')
                                </nav>
                            </div>

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
    </body>
</html>
