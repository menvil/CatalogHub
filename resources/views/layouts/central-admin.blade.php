@php
    $centralUser ??= auth()->user();
    $centralAdminNavigation = $centralUser instanceof \App\Models\User
        ? app(\App\Navigation\CentralNavigationRegistry::class)->visibleItemsFor($centralUser)
        : [];
    $documentTitle = trim($__env->yieldContent('pageTitle', $pageTitle ?? $title ?? 'Central Admin'));
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? $documentTitle }} - {{ config('app.name', 'CatalogHub') }}</title>

        @stack('head')
        @vite(['resources/css/central-admin.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen overflow-x-hidden bg-admin-background font-sans text-admin-text antialiased">
        <a href="#central-main-content" class="sr-only focus:not-sr-only">Skip to main content</a>

        <div
            class="min-h-screen lg:flex"
            data-admin-layout="central"
            data-central-shell
            data-central-sidebar-collapsed="false"
            data-central-sidebar-persist="{{ ($acceptance ?? false) ? 'false' : 'true' }}"
            @if (isset($centralShellPreviewState)) data-central-preview-state="{{ $centralShellPreviewState }}" @endif
            data-presentation-context="central-admin"
        >
            <x-central.sidebar
                :items="$centralAdminNavigation"
                :active-nav="$activeNav ?? null"
                data-central-sidebar
            />

            <div class="min-w-0 flex-1">
                @include('central.components.header', ['centralUser' => $centralUser])

                <main id="central-main-content" class="px-admin-page py-admin-section" tabindex="-1">
                    <div class="mx-auto max-w-7xl space-y-admin-section">
                        <x-admin.flash-messages />

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

        @livewireScripts
        @stack('scripts')
    </body>
</html>
