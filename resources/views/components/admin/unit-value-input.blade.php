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
    $unitOptions = collect($availableUnits)->mapWithKeys(function ($option): array {
        $value = is_array($option) ? ($option['value'] ?? $option['code'] ?? '') : (string) $option;
        $label = is_array($option) ? ($option['label'] ?? $value) : strtoupper((string) $option);

        return [(string) $value => (string) $label];
    })->all();
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
            class="w-full rounded-admin-input border bg-admin-surface px-3 py-2 text-sm text-admin-text placeholder:text-admin-muted focus:border-admin-primary focus:outline-none focus:ring-2 focus:ring-admin-primary/20 {{ $error ? 'border-admin-danger' : 'border-admin-border' }}"
            placeholder="Value"
        >

        <div class="admin-unit-select">
            <x-ui.form.select
                :id="$selectId"
                :name="$selectId"
                label="Unit"
                :options="$unitOptions"
                :selected="$unit"
                placeholder="Select a unit"
            />
        </div>
    </div>

    <div class="rounded-admin-input border border-admin-border bg-admin-surface-muted px-3 py-2 text-sm text-admin-muted">
        <span class="font-medium text-admin-text">Canonical preview:</span>
        <span>{{ is_null($canonicalPreview) ? 'Not calculated in Phase 2' : $canonicalPreview }}</span>
    </div>

    @if ($error)
        <p id="{{ $inputId }}-error" class="text-sm text-admin-danger">{{ $error }}</p>
    @endif
</div>
