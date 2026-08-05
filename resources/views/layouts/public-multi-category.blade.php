<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale ?? app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ trim($__env->yieldContent('title', $site->name ?? config('app.name', 'CatalogHub'))) }}</title>
        @stack('head')
        @vite(['resources/css/public.css', 'resources/js/app.js'])
    </head>
    <body
        class="min-h-screen bg-foundation-canvas font-foundation-sans text-foundation-text antialiased"
        data-presentation-context="public-site"
        data-public-layout="multi-category"
        data-public-theme="{{ $theme->identifier->value }}"
    >
        <div class="flex min-h-screen flex-col">
            <header class="border-b border-foundation-border bg-foundation-surface" data-public-header>
                <div class="mx-auto flex min-h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                    <a href="{{ $publicNavigation['home'] ?? '/' }}" class="text-foundation-title font-semibold">{{ $site->name ?? config('app.name', 'CatalogHub') }}</a>
                    @if (filled($publicNavigation['search'] ?? null))
                        <a href="{{ $publicNavigation['search'] }}" class="text-foundation-label text-foundation-text-muted" data-public-search-slot>Search</a>
                    @else
                        <span class="text-foundation-label text-foundation-text-muted" aria-disabled="true" data-public-search-slot>Search unavailable</span>
                    @endif
                </div>
                <nav class="border-t border-foundation-border" aria-label="Category navigation" data-public-category-slot>
                    <div class="mx-auto w-full max-w-7xl px-4 py-3 text-foundation-label text-foundation-text-muted sm:px-6">
                        Category navigation becomes available with catalog data.
                    </div>
                </nav>
            </header>

            <main id="main-content" class="mx-auto grid w-full max-w-7xl flex-1 gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[14rem_minmax(0,1fr)]">
                <aside class="hidden rounded-foundation-card border border-foundation-border bg-foundation-surface p-foundation-card text-foundation-label text-foundation-text-muted lg:block" aria-label="Catalog navigation slot">
                    Catalog navigation slot
                </aside>
                <section class="min-w-0" aria-label="Public content">
                    @yield('content')
                </section>
            </main>

            <footer class="border-t border-foundation-border bg-foundation-surface" data-public-footer>
                <div class="mx-auto w-full max-w-7xl px-4 py-6 text-foundation-label text-foundation-text-muted sm:px-6">
                    &copy; {{ now()->year }} {{ $site->name ?? config('app.name', 'CatalogHub') }}
                </div>
            </footer>
        </div>
        @stack('scripts')
    </body>
</html>
