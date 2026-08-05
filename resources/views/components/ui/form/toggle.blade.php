@props(['id', 'name', 'label', 'value' => '1', 'checked' => false, 'help' => null, 'error' => null, 'disabled' => false])
@php($describedBy = collect([filled($help) ? "{$id}-help" : null, filled($error) ? "{$id}-error" : null])->filter()->implode(' '))
<x-ui.form.field :id="$id" :label="$label" :help="$help" :error="$error">
    <input id="{{ $id }}" name="{{ $name }}" type="checkbox" role="switch" value="{{ $value }}" @checked($checked) @disabled($disabled)
        @if (filled($error)) aria-invalid="true" @endif @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class('peer sr-only') }}>
    <label for="{{ $id }}" aria-hidden="true" data-ui-toggle-indicator
        class="relative inline-flex h-5 w-9 cursor-pointer rounded-admin-badge border border-admin-border bg-admin-surface-muted after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-admin-text after:content-[''] after:transition-transform peer-checked:border-admin-primary peer-checked:bg-admin-primary peer-checked:after:translate-x-4 peer-checked:after:bg-white peer-focus-visible:ring-2 peer-focus-visible:ring-admin-primary/20 peer-disabled:cursor-not-allowed peer-disabled:opacity-60"></label>
</x-ui.form.field>
