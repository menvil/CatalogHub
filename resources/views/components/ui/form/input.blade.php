@props([
    'id', 'name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'error' => null,
    'required' => false, 'optional' => false, 'disabled' => false, 'readonly' => false,
])
@php
    $describedBy = collect([$help ? "{$id}-help" : null, $error ? "{$id}-error" : null])->filter()->implode(' ');
@endphp
<x-ui.form.field :id="$id" :label="$label" :required="$required" :optional="$optional" :help="$help" :error="$error">
    <input
        id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}"
        @required($required) @disabled($disabled) @readonly($readonly)
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class('block min-h-10 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text focus:border-admin-primary focus:outline-none focus:ring-2 focus:ring-admin-primary/20 disabled:cursor-not-allowed disabled:opacity-60') }}
    >
</x-ui.form.field>
