@props(['id', 'name', 'label', 'value' => '1', 'checked' => false, 'help' => null, 'error' => null, 'disabled' => false])
@php($describedBy = collect([$help ? "{$id}-help" : null, $error ? "{$id}-error" : null])->filter()->implode(' '))
<x-ui.form.field :id="$id" :label="$label" :help="$help" :error="$error">
    <input id="{{ $id }}" name="{{ $name }}" type="checkbox" role="switch" value="{{ $value }}" @checked($checked) @disabled($disabled)
        @if ($error) aria-invalid="true" @endif @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class('h-5 w-9 appearance-none rounded-admin-badge border border-admin-border bg-admin-surface-muted checked:border-admin-primary checked:bg-admin-primary focus:ring-2 focus:ring-admin-primary/20') }}>
</x-ui.form.field>
