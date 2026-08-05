<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>@yield('title') — {{ $errorContext?->siteName ?? config('app.name', 'CatalogHub') }}</title>
        @vite(['resources/css/public.css', 'resources/js/app.js'])
    </head>
    <body
        class="min-h-screen bg-foundation-canvas font-foundation-sans text-foundation-text antialiased"
        data-presentation-context="public-site"
        data-public-error="@yield('status')"
    >
        <main class="mx-auto flex min-h-screen max-w-3xl items-center px-4 py-12 sm:px-6">
            <section class="w-full rounded-foundation-card border border-foundation-border bg-foundation-surface p-foundation-card shadow-foundation-card" aria-labelledby="public-error-title">
                <p class="text-foundation-caption font-semibold uppercase tracking-wide text-foundation-text-muted">@yield('status')</p>
                <h1 id="public-error-title" class="mt-2 text-foundation-display font-semibold">@yield('heading')</h1>
                <p class="mt-3 text-foundation-body text-foundation-text-muted">@yield('message')</p>

                @if ($errorContext !== null)
                    <p class="mt-6 text-foundation-label">{{ $errorContext->siteName }}</p>
                    <a href="{{ $errorContext->homeUrl }}" class="mt-3 inline-flex text-foundation-label font-semibold text-foundation-accent-strong">Return home</a>
                @endif

                @if ($requestId !== null)
                    <p class="mt-6 text-foundation-caption text-foundation-text-muted">Request ID: <code>{{ $requestId }}</code></p>
                @endif
            </section>
        </main>
    </body>
</html>
