@props(['id', 'name', 'label', 'accept' => null, 'hint' => null, 'error' => null, 'required' => false, 'disabled' => false, 'multiple' => false])
@php($describedBy = collect([filled($hint) ? "{$id}-help" : null, filled($error) ? "{$id}-error" : null])->filter()->implode(' '))
<x-ui.form.field :id="$id" :label="$label" :required="$required" :help="$hint" :error="$error">
    <input id="{{ $id }}" name="{{ $multiple && ! str_ends_with($name, '[]') ? $name.'[]' : $name }}" type="file"
        @if ($accept !== null) accept="{{ $accept }}" @endif @if ($multiple) multiple @endif @required($required) @disabled($disabled)
        @if (filled($error)) aria-invalid="true" @endif @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class('block w-full rounded-admin-input border border-admin-border bg-admin-surface text-sm text-admin-text file:mr-3 file:border-0 file:border-r file:border-admin-border file:bg-admin-surface-muted file:px-3 file:py-2 file:text-sm file:font-medium disabled:opacity-60') }}>
</x-ui.form.field>
