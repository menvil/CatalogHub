@props([
    'id', 'name', 'label', 'prefix' => null, 'value' => null, 'help' => null, 'error' => null,
    'required' => false, 'disabled' => false, 'readonly' => false,
])
@php($describedBy = collect([$help ? "{$id}-help" : null, $error ? "{$id}-error" : null])->filter()->implode(' '))
<x-ui.form.field :id="$id" :label="$label" :required="$required" :help="$help" :error="$error">
    <div class="flex rounded-admin-input border border-admin-border bg-admin-surface focus-within:border-admin-primary focus-within:ring-2 focus-within:ring-admin-primary/20">
        @if (filled($prefix))<span class="flex items-center border-r border-admin-border bg-admin-surface-muted px-3 text-sm text-admin-muted">{{ $prefix }}</span>@endif
        <input
            id="{{ $id }}" name="{{ $name }}" type="text" value="{{ $value }}"
            @required($required) @disabled($disabled) @readonly($readonly)
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->class('min-h-10 min-w-0 flex-1 bg-transparent px-3 py-2 text-sm text-admin-text outline-none disabled:cursor-not-allowed disabled:opacity-60') }}
        >
    </div>
</x-ui.form.field>
