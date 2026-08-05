@props(['id', 'name', 'label', 'value' => '1', 'checked' => false, 'help' => null, 'error' => null, 'disabled' => false])
@php($describedBy = collect([filled($help) ? "{$id}-help" : null, filled($error) ? "{$id}-error" : null])->filter()->implode(' '))
<x-ui.form.field :id="$id" :label="$label" :help="$help" :error="$error">
    <input id="{{ $id }}" name="{{ $name }}" type="checkbox" role="switch" value="{{ $value }}" @checked($checked) @disabled($disabled)
        data-ui-toggle-indicator
        @if (filled($error)) aria-invalid="true" @endif @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class("relative h-5 w-9 appearance-none rounded-admin-badge border border-admin-border bg-admin-surface-muted before:absolute before:left-0.5 before:top-0.5 before:h-4 before:w-4 before:rounded-full before:bg-admin-text before:content-[''] before:transition-transform checked:border-admin-primary checked:bg-admin-primary checked:before:translate-x-4 checked:before:bg-white focus:ring-2 focus:ring-admin-primary/20") }}>
</x-ui.form.field>
