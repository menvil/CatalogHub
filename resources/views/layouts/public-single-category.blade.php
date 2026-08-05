<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale ?? app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <x-public.seo-meta :metadata="$seoMetadata" />
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
            <x-public.header :site="$site" :navigation="$publicNavigation ?? []" :locale-options="$publicLocaleOptions ?? []" variant="single" />

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

            <x-public.footer :site="$site" variant="single" />
        </div>
        @stack('scripts')
    </body>
</html>
