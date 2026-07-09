@props([
    'value' => null,
    'unit' => null,
    'availableUnits' => [],
    'label' => null,
    'canonicalPreview' => null,
    'error' => null,
    'id' => null,
])

@php
    $inputId = $id ?: 'unit-value-'.(\Illuminate\Support\Str::slug($label ?? 'value') ?: 'value').'-'.\Illuminate\Support\Str::random(6);
    $selectId = $inputId.'-unit';
@endphp

<div
    {{ $attributes->class('space-y-admin-field') }}
    data-admin-unit-value-input
>
    @if ($label)
        <label for="{{ $inputId }}" class="block text-sm font-semibold text-admin-text">{{ $label }}</label>
    @endif

    <div class="grid gap-admin-field sm:grid-cols-[minmax(0,1fr)_10rem]">
        <input
            id="{{ $inputId }}"
            type="number"
            step="any"
            value="{{ $value }}"
            aria-invalid="{{ $error ? 'true' : 'false' }}"
            @if ($error) aria-describedby="{{ $inputId }}-error" @endif
            class="w-full rounded-admin-input border bg-admin-surface px-3 py-2 text-sm text-admin-text placeholder:text-admin-muted focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary {{ $error ? 'border-admin-danger' : 'border-admin-border' }}"
            placeholder="Value"
        >

        <label for="{{ $selectId }}" class="sr-only">Unit</label>
        <select
            id="{{ $selectId }}"
            class="w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary"
        >
            @foreach ($availableUnits as $option)
                @php
                    $unitValue = is_array($option) ? ($option['value'] ?? $option['code'] ?? '') : (string) $option;
                    $unitLabel = is_array($option) ? ($option['label'] ?? $unitValue) : strtoupper((string) $option);
                @endphp

                <option value="{{ $unitValue }}" @selected($unit === $unitValue)>{{ $unitLabel }}</option>
            @endforeach
        </select>
    </div>

    <div class="rounded-admin-input border border-admin-border bg-admin-surface-muted px-3 py-2 text-sm text-admin-muted">
        <span class="font-medium text-admin-text">Canonical preview:</span>
        <span>{{ is_null($canonicalPreview) ? 'Not calculated in Phase 2' : $canonicalPreview }}</span>
    </div>

    @if ($error)
        <p id="{{ $inputId }}-error" class="text-sm text-admin-danger">{{ $error }}</p>
    @endif
</div>
