@props([
    'label',
    'variant' => 'neutral',
    'icon' => null,
    'size' => 'md',
])

@php
    $variantClasses = [
        'success' => 'bg-admin-success-soft text-admin-success ring-admin-success/20',
        'warning' => 'bg-admin-warning-soft text-admin-warning ring-admin-warning/25',
        'danger' => 'bg-admin-danger-soft text-admin-danger ring-admin-danger/25',
        'info' => 'bg-admin-info-soft text-admin-info ring-admin-info/25',
        'neutral' => 'bg-admin-surface-muted text-admin-muted ring-admin-border',
    ];

    $sizeClasses = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-3 py-1 text-sm',
    ];

    $variantClass = $variantClasses[$variant] ?? $variantClasses['neutral'];
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<span
    {{ $attributes->class([
        'inline-flex max-w-full items-center gap-1.5 rounded-admin-badge font-medium ring-1 ring-inset',
        $variantClass,
        $sizeClass,
    ]) }}
    data-admin-status-badge="{{ $variant }}"
>
    @if ($icon)
        <span aria-hidden="true">{{ $icon }}</span>
    @endif

    <span class="truncate">{{ $label }}</span>
</span>
