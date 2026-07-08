@php
    $centralAdminNavigation = [
        ['key' => 'dashboard', 'label' => 'Dashboard'],
        ['key' => 'products', 'label' => 'Products'],
        ['key' => 'categories', 'label' => 'Categories'],
        ['key' => 'brands', 'label' => 'Brands'],
        ['key' => 'imports', 'label' => 'Imports'],
        ['key' => 'media', 'label' => 'Media'],
        ['key' => 'translations', 'label' => 'Translations'],
        ['key' => 'price-sources', 'label' => 'Price Sources'],
        ['key' => 'sites', 'label' => 'Sites'],
        ['key' => 'sync', 'label' => 'Sync'],
        ['key' => 'backups', 'label' => 'Backups'],
        ['key' => 'settings', 'label' => 'Settings'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? 'Central Admin' }} - {{ config('app.name', 'CatalogHub') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen overflow-x-hidden bg-admin-background font-sans text-admin-text antialiased">
        <div class="min-h-screen lg:flex" data-admin-layout="central">
            <x-admin.sidebar
                context="central"
                :items="$centralAdminNavigation"
                :active-nav="$activeNav ?? null"
            />

            <div class="min-w-0 flex-1">
                <x-admin.topbar context-label="Central Admin" search-placeholder="Search canonical catalog">
                    <x-slot:title>
                        <h1 class="text-xl font-semibold text-admin-text">
                            @yield('pageTitle', $pageTitle ?? 'Central Admin')
                        </h1>
                    </x-slot:title>
                </x-admin.topbar>

                <main class="px-admin-page py-admin-section">
                    <div class="mx-auto max-w-7xl space-y-admin-section">
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

                        <section aria-label="Central Admin content">
                            {{ $slot ?? '' }}
                            @yield('content')
                        </section>
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
