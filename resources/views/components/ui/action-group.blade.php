@props([
    'label' => 'Actions',
    'align' => 'end',
])

@php
    $alignment = [
        'start' => 'justify-start',
        'end' => 'justify-end',
        'between' => 'justify-between',
    ];

    throw_unless(array_key_exists($align, $alignment), \InvalidArgumentException::class, "Unknown action group alignment [{$align}].");
@endphp

<div
    {{ $attributes->class(['flex max-w-full flex-wrap items-center gap-admin-field overflow-x-auto', $alignment[$align]]) }}
    role="group"
    aria-label="{{ $label }}"
    data-ui-action-group
>
    {{ $slot }}
</div>
