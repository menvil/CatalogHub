@props(['value', 'label' => 'Identifier'])

@php
    $identifier = trim((string) $value);
    throw_if($identifier === '', \InvalidArgumentException::class, 'Identifiers cannot be empty.');
@endphp

<code {{ $attributes->class('inline-flex min-h-7 items-center rounded-admin-input bg-admin-surface-muted px-2 font-mono text-sm leading-none text-admin-text') }} aria-label="{{ $label }}: {{ $identifier }}" data-ui-identifier>{{ $identifier }}</code>
