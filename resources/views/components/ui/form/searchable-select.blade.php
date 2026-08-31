@props([
    'id', 'name', 'label', 'options' => [], 'selected' => null, 'placeholder' => 'Select an option',
    'searchPlaceholder' => 'Search…', 'help' => null, 'error' => null, 'required' => false,
    'disabled' => false, 'clearable' => false, 'emptyMessage' => 'No matching options.',
    'remote' => null, 'loadingMessage' => 'Loading options…', 'errorMessage' => 'Unable to load options.',
])
@php
    $selected = $selected === null || $selected === '' ? null : (string) $selected;
    $selectedOption = collect($options)->first(
        fn (array $option): bool => (string) $option['value'] === $selected,
    );
    $selectedLabel = is_array($selectedOption) ? (string) $selectedOption['label'] : '';
    $describedBy = collect([
        $attributes->get('aria-describedby'),
        filled($help) ? "{$id}-help" : null,
        filled($error) ? "{$id}-error" : null,
    ])->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')->implode(' ');
@endphp
<x-ui.form.field :id="$id" :control-id="$id.'-combobox'" :label="$label" :required="$required" :help="$help" :error="$error">
    <div
        class="relative min-w-0"
        data-ui-searchable-select
        data-selected-label="{{ $selectedLabel }}"
        data-empty-message="{{ $emptyMessage }}"
        @if (filled($remote)) data-search-url="{{ $remote }}" data-loading-message="{{ $loadingMessage }}" data-error-message="{{ $errorMessage }}" @endif
    >
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            class="sr-only"
            tabindex="-1"
            aria-hidden="true"
            data-ui-searchable-select-native
            @required($required)
            @disabled($disabled)
        >
            <option value="">{{ $placeholder }}</option>
            @foreach ($options as $option)
                <option value="{{ $option['value'] }}" @selected($selected === (string) $option['value'])>{{ $option['label'] }}</option>
            @endforeach
        </select>

        <div class="flex min-w-0 items-stretch">
            <div class="relative min-w-0 flex-1">
                <input
                    id="{{ $id }}-combobox"
                    type="text"
                    value="{{ $selectedLabel }}"
                    placeholder="{{ $placeholder }}"
                    autocomplete="off"
                    role="combobox"
                    aria-autocomplete="list"
                    aria-expanded="false"
                    aria-controls="{{ $id }}-listbox"
                    @if (filled($error)) aria-invalid="true" @endif
                    @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
                    @disabled($disabled)
                    {{ $attributes->except('aria-describedby')->class('block min-h-10 w-full truncate rounded-admin-input border border-admin-border bg-admin-surface py-2 pl-3 pr-10 text-sm text-admin-text placeholder:text-admin-muted focus:border-admin-primary focus:outline-none focus:ring-2 focus:ring-admin-primary/20 disabled:cursor-not-allowed disabled:opacity-60') }}
                    data-ui-searchable-select-input
                    data-search-placeholder="{{ $searchPlaceholder }}"
                >
                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-admin-muted">
                    <x-ui.icon name="chevron-down" decorative size="sm" data-ui-searchable-select-chevron />
                </span>
            </div>
            @if ($clearable)
                <button
                    type="button"
                    class="ml-2 min-h-10 shrink-0 rounded-admin-input border border-admin-border px-3 text-sm text-admin-muted hover:bg-admin-surface-muted focus:outline-none focus:ring-2 focus:ring-admin-primary/20 disabled:cursor-not-allowed disabled:opacity-60"
                    aria-label="Clear {{ $label }}"
                    data-ui-searchable-select-clear
                    @disabled($disabled)
                >Clear</button>
            @endif
        </div>

        <div
            id="{{ $id }}-listbox"
            class="absolute z-30 mt-1 max-h-64 w-full min-w-0 overflow-y-auto rounded-admin-input border border-admin-border bg-admin-surface p-1 shadow-admin-modal"
            role="listbox"
            data-ui-searchable-select-listbox
            hidden
        >
            @foreach ($options as $index => $option)
                <div
                    id="{{ $id }}-option-{{ $index }}"
                    class="flex min-h-9 cursor-pointer items-center rounded-admin-input px-3 py-2 text-sm text-admin-text hover:bg-admin-surface-muted aria-selected:bg-admin-primary aria-selected:text-white"
                    role="option"
                    aria-selected="{{ $selected === (string) $option['value'] ? 'true' : 'false' }}"
                    data-ui-searchable-select-option
                    data-value="{{ $option['value'] }}"
                    data-label="{{ $option['label'] }}"
                    data-search="{{ $option['search'] }}"
                >{{ $option['label'] }}</div>
            @endforeach
            <p class="px-3 py-2 text-sm text-admin-muted" data-ui-searchable-select-empty hidden>{{ $emptyMessage }}</p>
            @if (filled($remote))
                <p class="px-3 py-2 text-sm text-admin-muted" data-ui-searchable-select-loading hidden>{{ $loadingMessage }}</p>
            @endif
        </div>
    </div>
</x-ui.form.field>
