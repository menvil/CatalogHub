@props([
    'site',
    'navigation' => [],
    'localeOptions' => [],
    'variant' => 'multi',
])

@php($focused = $variant === 'single')

<header
    data-public-header
    data-public-header-variant="{{ $focused ? 'single' : 'multi' }}"
    @class([
        'border-b border-foundation-border',
        'bg-foundation-text text-foundation-surface' => $focused,
        'bg-foundation-surface text-foundation-text' => ! $focused,
    ])
>
    <div @class([
        'mx-auto flex min-h-16 w-full flex-col items-stretch justify-between gap-3 px-4 py-3 sm:flex-row sm:items-center sm:gap-4 sm:px-6',
        'max-w-5xl' => $focused,
        'max-w-7xl' => ! $focused,
    ])>
        <a href="{{ $navigation['home'] ?? '/' }}"
            class="min-w-0 truncate text-foundation-title font-semibold"
            data-public-home-link
        >
            {{ $site->name ?? config('app.name', 'CatalogHub') }}
        </a>

        <nav class="flex w-full shrink-0 flex-wrap items-center justify-start gap-3 sm:w-auto sm:gap-4" aria-label="Primary navigation">
            @if (filled($navigation['search'] ?? null))
                <a href="{{ $navigation['search'] }}" class="text-foundation-label" data-public-search-slot>Search</a>
            @else
                <span class="text-foundation-label opacity-75" data-public-search-slot>Search unavailable</span>
            @endif

            <x-public.locale-selector :options="$localeOptions" />
        </nav>
    </div>
</header>
