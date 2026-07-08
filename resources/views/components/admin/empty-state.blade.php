@props([
    'title',
    'description' => null,
    'icon' => null,
    'variant' => 'default',
])

@php
    $variantClasses = [
        'default' => 'bg-admin-surface text-admin-muted ring-admin-border',
        'warning' => 'bg-admin-warning-soft text-admin-warning ring-admin-warning/25',
        'error' => 'bg-admin-danger-soft text-admin-danger ring-admin-danger/25',
    ];

    $variantClass = $variantClasses[$variant] ?? $variantClasses['default'];
@endphp

<div
    {{ $attributes->class('rounded-admin-card border border-dashed border-admin-border bg-admin-surface p-8 text-center') }}
    data-admin-empty-state="{{ $variant }}"
>
    @if (! is_null($icon) && $icon !== '')
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-admin-badge ring-1 ring-inset {{ $variantClass }}" aria-hidden="true">
            {{ $icon }}
        </div>
    @endif

    <h2 class="text-base font-semibold text-admin-text">{{ $title }}</h2>

    @if ($description)
        <p class="mx-auto mt-2 max-w-md text-sm text-admin-muted">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-5 flex justify-center">
            {{ $action }}
        </div>
    @endisset
</div>
