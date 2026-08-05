@props([
    'name',
    'label' => null,
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'h-4 w-4',
        'md' => 'h-5 w-5',
        'lg' => 'h-6 w-6',
    ];
    $component = \App\Support\DesignSystem\FoundationDesignSystem::HEROICON_COMPONENTS[$name]
        ?? throw new \InvalidArgumentException("Unknown foundation icon [{$name}].");
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<span
    {{ $attributes->class(['inline-flex shrink-0 items-center justify-center', $sizeClass]) }}
    @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif
    data-foundation-icon="{{ $name }}"
>
    <x-dynamic-component :component="$component" class="h-full w-full" />
</span>
