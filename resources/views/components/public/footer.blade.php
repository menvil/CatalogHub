@props([
    'site',
    'variant' => 'multi',
])

@php($focused = $variant === 'single')

<footer
    data-public-footer
    @class([
        'border-t border-foundation-border',
        'bg-foundation-text text-foundation-surface' => $focused,
        'bg-foundation-surface text-foundation-text-muted' => ! $focused,
    ])
>
    <div @class([
        'mx-auto w-full px-4 py-6 text-foundation-label sm:px-6',
        'max-w-5xl' => $focused,
        'max-w-7xl' => ! $focused,
    ])>
        &copy; {{ now()->year }} {{ $site->name ?? config('app.name', 'CatalogHub') }}
    </div>
</footer>
