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
    $drawerTitleId = 'admin-drawer-title-'.\Illuminate\Support\Str::random(8);
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
    data-admin-drawer-contained="{{ $contained ? 'true' : 'false' }}"
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
        aria-labelledby="{{ $drawerTitleId }}"
    >
        <header class="flex items-start justify-between gap-admin-field border-b border-admin-border p-admin-card">
            <h2 id="{{ $drawerTitleId }}" class="text-base font-semibold text-admin-text">{{ $title }}</h2>

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

        @if (isset($footer) || isset($actions))
            <footer class="border-t border-admin-border p-admin-card">
                @isset($footer)
                    {{ $footer }}
                @endisset

                @isset($actions)
                    <div @class(['mt-admin-field' => isset($footer)])>
                        {{ $actions }}
                    </div>
                @endisset
            </footer>
        @endif
    </aside>
</div>
