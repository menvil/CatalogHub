@props([
    'id', 'name', 'label', 'value' => null, 'help' => null, 'error' => null,
    'required' => false, 'disabled' => false,
])
@php
    $describedBy = collect([
        $attributes->get('aria-describedby'),
        filled($help) ? "{$id}-help" : null,
        filled($error) ? "{$id}-error" : null,
    ])->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')->implode(' ');
    $controlAttributes = $attributes->except('aria-describedby');
@endphp
<x-ui.form.field :id="$id" :label="$label" :required="$required" :help="$help" :error="$error">
    <div class="relative" data-ui-date-picker="{{ $id }}">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="date"
            value="{{ $value }}"
            @required($required)
            @disabled($disabled)
            @if (filled($error)) aria-invalid="true" @endif
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            {{ $controlAttributes->class('block min-h-10 w-full cursor-pointer rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 pr-11 text-sm text-admin-text focus:border-admin-primary focus:outline-none focus:ring-2 focus:ring-admin-primary/20 disabled:cursor-not-allowed disabled:opacity-60') }}
        >
        <button
            type="button"
            class="absolute inset-y-0 right-0 inline-flex w-11 cursor-pointer items-center justify-center text-admin-muted hover:text-admin-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-admin-primary disabled:cursor-not-allowed disabled:opacity-60"
            aria-label="Open {{ $label }} calendar"
            data-ui-date-picker-trigger
            @disabled($disabled)
        ><x-ui.icon name="calendar-days" decorative size="sm" /></button>
    </div>
</x-ui.form.field>
