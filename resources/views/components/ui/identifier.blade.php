@props(['value', 'label' => 'Identifier'])

@php
    $identifier = trim((string) $value);
    throw_if($identifier === '', \InvalidArgumentException::class, 'Identifiers cannot be empty.');
@endphp

<code {{ $attributes->class('inline-flex rounded-admin-input bg-admin-surface-muted px-2 py-1 font-mono text-sm text-admin-text') }} aria-label="{{ $label }}: {{ $identifier }}" data-ui-identifier>{{ $identifier }}</code>
