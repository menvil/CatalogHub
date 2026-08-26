@props([
    'id', 'name', 'label', 'value' => null, 'help' => null, 'error' => null,
    'required' => false, 'optional' => false, 'disabled' => false,
])
@php
    $textValue = is_string($value) ? $value : '';
    $validValue = preg_match('/\A#[0-9A-Fa-f]{6}\z/', $textValue) === 1 ? strtoupper($textValue) : null;
    $describedBy = collect([
        $attributes->get('aria-describedby'),
        filled($help) ? "{$id}-help" : null,
        filled($error) ? "{$id}-error" : null,
    ])->filter(fn (mixed $item): bool => is_string($item) && trim($item) !== '')->implode(' ');
    $controlAttributes = $attributes->except('aria-describedby');
@endphp
<x-ui.form.field :id="$id" :label="$label" :required="$required" :optional="$optional" :help="$help" :error="$error">
    <div class="flex min-w-0 items-center gap-2" data-ui-color-input>
        <input
            id="{{ $id }}-picker"
            type="color"
            value="{{ $validValue ?? '#000000' }}"
            aria-label="Choose {{ $label }}"
            @disabled($disabled)
            class="h-10 w-12 shrink-0 cursor-pointer rounded-admin-input border border-admin-border bg-admin-surface p-1 disabled:cursor-not-allowed disabled:opacity-60"
            data-ui-color-input-picker
        >
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="text"
            value="{{ $textValue }}"
            placeholder="#1428A0"
            maxlength="7"
            inputmode="text"
            autocomplete="off"
            spellcheck="false"
            @required($required)
            @disabled($disabled)
            @if (filled($error)) aria-invalid="true" @endif
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            {{ $controlAttributes->class('min-h-10 min-w-0 flex-1 rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 font-foundation-mono text-sm uppercase text-admin-text focus:border-admin-primary focus:outline-none focus:ring-2 focus:ring-admin-primary/20 disabled:cursor-not-allowed disabled:opacity-60') }}
            data-ui-color-input-text
        >
    </div>
</x-ui.form.field>
