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
        class="min-h-screen bg-foundation-surface font-foundation-sans text-foundation-text antialiased"
        data-presentation-context="public-site"
        data-public-layout="single-category"
        data-public-theme="{{ $theme->identifier->value }}"
    >
        <div class="flex min-h-screen flex-col">
            <header class="bg-foundation-text text-foundation-surface" data-public-header>
                <div class="mx-auto flex min-h-16 w-full max-w-5xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                    <a href="{{ $publicNavigation['home'] ?? '/' }}" class="text-foundation-title font-semibold">{{ $site->name ?? config('app.name', 'CatalogHub') }}</a>
                    <span class="text-foundation-label text-foundation-surface">Focused catalogue</span>
                </div>
            </header>

            <main id="main-content" class="w-full flex-1">
                <section class="border-b border-foundation-border bg-foundation-accent-surface" data-public-focused-hero>
                    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
                        <p class="text-foundation-caption font-semibold uppercase tracking-wide text-foundation-accent-strong">Single category</p>
                        <h1 class="mt-2 text-foundation-display font-semibold">{{ $site->name ?? 'Focused catalogue' }}</h1>
                    </div>
                </section>

                <section class="border-b border-foundation-border bg-foundation-surface-muted" data-public-filter-slot>
                    <div class="mx-auto max-w-5xl px-4 py-3 text-foundation-label text-foundation-text-muted sm:px-6">
                        Filter and search integration point
                    </div>
                </section>

                <section class="mx-auto max-w-5xl px-4 py-8 sm:px-6" aria-label="Public content">
                    @yield('content')
                </section>
            </main>

            <footer class="bg-foundation-text text-foundation-surface" data-public-footer>
                <div class="mx-auto max-w-5xl px-4 py-6 text-foundation-label sm:px-6">
                    &copy; {{ now()->year }} {{ $site->name ?? config('app.name', 'CatalogHub') }}
                </div>
            </footer>
        </div>
        @stack('scripts')
    </body>
</html>
