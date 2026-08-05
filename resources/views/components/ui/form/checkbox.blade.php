@props(['id', 'name', 'label', 'value' => '1', 'checked' => false, 'help' => null, 'error' => null, 'disabled' => false])
@php($describedBy = collect([$help ? "{$id}-help" : null, $error ? "{$id}-error" : null])->filter()->implode(' '))
<x-ui.form.field :id="$id" :label="$label" :help="$help" :error="$error">
    <input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="{{ $value }}" @checked($checked) @disabled($disabled)
        @if ($error) aria-invalid="true" @endif @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class('h-4 w-4 rounded border-admin-border text-admin-primary focus:ring-admin-primary') }}>
</x-ui.form.field>
