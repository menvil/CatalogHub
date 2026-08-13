@props([
    'id', 'name', 'label', 'options' => [], 'selected' => null, 'placeholder' => null,
    'help' => null, 'error' => null, 'required' => false, 'disabled' => false,
])
@php
    $describedBy = collect([
        $attributes->get('aria-describedby'),
        filled($help) ? "{$id}-help" : null,
        filled($error) ? "{$id}-error" : null,
    ])->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')->implode(' ');
    $controlAttributes = $attributes->except('aria-describedby');
    $selectedLabel = $placeholder;
    foreach ($options as $value => $option) {
        if (is_array($option)) {
            foreach ($option as $groupValue => $groupLabel) {
                if ((string) $selected === (string) $groupValue) {
                    $selectedLabel = $groupLabel;
                }
            }
        } elseif ((string) $selected === (string) $value) {
            $selectedLabel = $option;
        }
    }
@endphp
<x-ui.form.field :id="$id" :control-id="$id.'-trigger'" :label="$label" :required="$required" :help="$help" :error="$error">
    <div class="relative" data-ui-select>
        <select id="{{ $id }}" name="{{ $name }}" @required($required) @disabled($disabled)
            {{ $controlAttributes->class('sr-only') }} data-ui-select-native tabindex="-1" aria-hidden="true">
            @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
            @foreach ($options as $value => $option)
                @if (is_array($option))
                    <optgroup label="{{ $value }}">
                        @foreach ($option as $groupValue => $groupLabel)<option value="{{ $groupValue }}" @selected((string) $selected === (string) $groupValue)>{{ $groupLabel }}</option>@endforeach
                    </optgroup>
                @else
                    <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $option }}</option>
                @endif
            @endforeach
        </select>
        <button
            id="{{ $id }}-trigger"
            type="button"
            class="flex min-h-10 w-full cursor-pointer items-center justify-between gap-3 rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-left text-sm text-admin-text hover:bg-admin-surface-muted focus:border-admin-primary focus:outline-none focus:ring-2 focus:ring-admin-primary/20 disabled:cursor-not-allowed disabled:opacity-60"
            aria-haspopup="listbox"
            aria-expanded="false"
            aria-controls="{{ $id }}-menu"
            @if (filled($error)) aria-invalid="true" @endif
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            data-ui-select-trigger
            @disabled($disabled)
        >
            <span class="truncate" data-ui-select-value>{{ $selectedLabel ?? 'Select an option' }}</span>
            <x-ui.icon name="chevron-down" decorative size="sm" class="transition-transform" data-ui-select-chevron />
        </button>
        <div
            id="{{ $id }}-menu"
            class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-admin-input border border-admin-border bg-admin-surface p-1 shadow-admin-modal"
            role="listbox"
            data-ui-select-menu
            hidden
        >
            @if ($placeholder !== null)
                <button type="button" class="flex min-h-9 w-full cursor-pointer items-center rounded-admin-input px-3 py-2 text-left text-sm text-admin-muted hover:bg-admin-surface-muted focus:bg-admin-surface-muted focus:outline-none" role="option" data-ui-select-option data-value="" aria-selected="{{ $selected === null || $selected === '' ? 'true' : 'false' }}">{{ $placeholder }}</button>
            @endif
            @foreach ($options as $value => $option)
                @if (is_array($option))
                    <p class="px-3 pb-1 pt-2 text-xs font-semibold uppercase tracking-wide text-admin-muted" role="presentation">{{ $value }}</p>
                    @foreach ($option as $groupValue => $groupLabel)
                        <button type="button" class="flex min-h-9 w-full cursor-pointer items-center rounded-admin-input px-3 py-2 text-left text-sm text-admin-text hover:bg-admin-surface-muted focus:bg-admin-surface-muted focus:outline-none aria-selected:bg-admin-primary aria-selected:text-white" role="option" data-ui-select-option data-value="{{ $groupValue }}" aria-selected="{{ (string) $selected === (string) $groupValue ? 'true' : 'false' }}">{{ $groupLabel }}</button>
                    @endforeach
                @else
                    <button type="button" class="flex min-h-9 w-full cursor-pointer items-center rounded-admin-input px-3 py-2 text-left text-sm text-admin-text hover:bg-admin-surface-muted focus:bg-admin-surface-muted focus:outline-none aria-selected:bg-admin-primary aria-selected:text-white" role="option" data-ui-select-option data-value="{{ $value }}" aria-selected="{{ (string) $selected === (string) $value ? 'true' : 'false' }}">{{ $option }}</button>
                @endif
            @endforeach
        </div>
    </div>
</x-ui.form.field>
