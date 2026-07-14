<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale ?? app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php($fallbackTitle = trim($__env->yieldContent('title', $title ?? ($site->name ?? config('app.name', 'CatalogHub')))))
        @include('public.partials.seo', ['seo' => $seo ?? [], 'fallbackTitle' => $fallbackTitle])

        @stack('head')
        @vite(['resources/css/public.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-950 antialiased">
        <div class="flex min-h-screen flex-col">
            @include('public.partials.header')

            <main id="main-content" class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
                @yield('content')
            </main>

            @include('public.partials.footer')
        </div>

        @stack('scripts')
    </body>
</html>
