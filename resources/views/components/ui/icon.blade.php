@props([
    'name',
    'label' => null,
    'size' => 'md',
])

@php
    $approvedIcons = [
        'check-circle' => 'heroicon-o-check-circle',
        'exclamation-triangle' => 'heroicon-o-exclamation-triangle',
        'x-circle' => 'heroicon-o-x-circle',
        'information-circle' => 'heroicon-o-information-circle',
        'eye' => 'heroicon-o-eye',
        'pencil-square' => 'heroicon-o-pencil-square',
    ];
    $sizes = [
        'sm' => 'h-4 w-4',
        'md' => 'h-5 w-5',
        'lg' => 'h-6 w-6',
    ];
    $component = $approvedIcons[$name] ?? $approvedIcons['information-circle'];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<span
    {{ $attributes->class(['inline-flex shrink-0 items-center justify-center', $sizeClass]) }}
    @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif
    data-foundation-icon="{{ $name }}"
>
    <x-dynamic-component :component="$component" class="h-full w-full" />
</span>
