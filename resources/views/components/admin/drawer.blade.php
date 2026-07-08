@props([
    'title',
    'position' => 'right',
    'size' => 'md',
    'backdrop' => true,
    'open' => true,
    'contained' => false,
])

@php
    $positionClasses = [
        'right' => 'right-0 border-l',
        'left' => 'left-0 border-r',
    ];

    $sizeClasses = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-2xl',
    ];

    $positionClass = $positionClasses[$position] ?? $positionClasses['right'];
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<div
    {{ $attributes->class([
        'inset-0 z-50',
        'absolute' => $contained,
        'fixed' => ! $contained,
        'hidden' => ! $open,
    ]) }}
    data-admin-drawer
    data-admin-drawer-open="{{ $open ? 'true' : 'false' }}"
>
    @if ($backdrop)
        <button
            type="button"
            class="absolute inset-0 bg-admin-text/35"
            data-admin-drawer-close
            aria-label="Close drawer backdrop"
        ></button>
    @endif

    <aside
        class="absolute top-0 flex h-full w-full {{ $sizeClass }} {{ $positionClass }} flex-col border-admin-border bg-admin-surface shadow-admin-floating"
        role="dialog"
        aria-modal="true"
        aria-labelledby="admin-drawer-title"
    >
        <header class="flex items-start justify-between gap-admin-field border-b border-admin-border p-admin-card">
            <h2 id="admin-drawer-title" class="text-base font-semibold text-admin-text">{{ $title }}</h2>

            <button
                type="button"
                class="rounded-admin-input border border-admin-border px-2 py-1 text-sm font-medium text-admin-muted focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary"
                data-admin-drawer-close
                aria-label="Close drawer"
            >
                Close
            </button>
        </header>

        <div class="min-h-0 flex-1 overflow-y-auto p-admin-card">
            {{ $slot }}
        </div>

        @isset($footer)
            <footer class="border-t border-admin-border p-admin-card">
                {{ $footer }}
            </footer>
        @endisset

        @isset($actions)
            <footer class="border-t border-admin-border p-admin-card">
                {{ $actions }}
            </footer>
        @endisset
    </aside>
</div>
