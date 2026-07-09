@props([
    'attributeLabel',
    'attributeCode' => null,
    'dataType' => 'text',
    'rawValue' => null,
    'normalizedValue' => null,
    'unitOptions' => [],
    'options' => [],
    'confidence' => null,
    'sourceLabel' => null,
])

@php
    $fieldId = 'attribute-'.(\Illuminate\Support\Str::slug($attributeCode ?? $attributeLabel) ?: 'value');
    $formatValue = function ($value): string {
        if (is_null($value)) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        }

        return (string) $value;
    };
    $normalizedDisplay = is_null($normalizedValue) ? 'Not normalized in Phase 2' : $formatValue($normalizedValue);
    $firstUnitOption = $unitOptions[0] ?? null;
    $selectedUnitValue = is_array($firstUnitOption) ? ($firstUnitOption['value'] ?? $firstUnitOption['code'] ?? null) : $firstUnitOption;
    $selectedUnitLabel = is_array($firstUnitOption) ? ($firstUnitOption['label'] ?? $selectedUnitValue) : (is_null($firstUnitOption) ? null : strtoupper((string) $firstUnitOption));
    $unitCanonicalPreview = is_null($normalizedValue)
        ? null
        : trim($normalizedDisplay.' '.($selectedUnitLabel ?? ''));
    $booleanValue = filter_var($normalizedValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $booleanValue = is_null($booleanValue) ? (bool) $normalizedValue : $booleanValue;
    $normalizedOptionValues = collect(\Illuminate\Support\Arr::wrap($normalizedValue))->map(fn ($value) => (string) $value)->all();
@endphp

<section
    {{ $attributes->class('rounded-admin-card border border-admin-border bg-admin-surface p-admin-card') }}
    data-admin-attribute-value-editor="{{ $dataType }}"
>
    <div class="flex flex-col gap-admin-field md:flex-row md:items-start md:justify-between">
        <div>
            <h2 class="text-base font-semibold text-admin-text">{{ $attributeLabel }}</h2>

            @if ($attributeCode)
                <p class="mt-1 text-xs font-medium uppercase tracking-wide text-admin-muted">{{ $attributeCode }}</p>
            @endif
        </div>

        <div class="flex flex-wrap gap-admin-field">
            <x-admin.status-badge :label="$dataType" variant="neutral" size="sm" />

            @if (! is_null($confidence))
                <x-admin.status-badge label="{{ $confidence }}% confidence" variant="{{ $confidence >= 80 ? 'success' : 'warning' }}" size="sm" />
            @endif
        </div>
    </div>

    <div class="mt-4 grid gap-admin-field lg:grid-cols-2">
        <div class="rounded-admin-input border border-admin-border bg-admin-surface-muted p-3">
            <label for="{{ $fieldId }}-raw" class="text-sm font-semibold text-admin-text">Raw value</label>
            <textarea
                id="{{ $fieldId }}-raw"
                rows="3"
                class="mt-2 w-full resize-y rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary"
                placeholder="Imported or source value"
            >{{ $rawValue }}</textarea>

            @if ($sourceLabel)
                <p class="mt-2 text-xs text-admin-muted">Source: {{ $sourceLabel }}</p>
            @endif
        </div>

        <div class="rounded-admin-input border border-admin-border bg-admin-surface-muted p-3">
            <p class="text-sm font-semibold text-admin-text">Normalized preview</p>
            <p class="mt-2 min-h-10 rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text">
                {{ $normalizedDisplay }}
            </p>
        </div>
    </div>

    <div class="mt-4 rounded-admin-input border border-admin-border bg-admin-surface-muted p-3">
        @if ($dataType === 'unit')
            <x-admin.unit-value-input
                label="Canonical unit value"
                :value="$normalizedValue"
                :unit="$selectedUnitValue"
                :available-units="$unitOptions"
                :canonical-preview="$unitCanonicalPreview"
            />
        @elseif ($dataType === 'boolean')
            <label class="inline-flex items-center gap-2 text-sm font-medium text-admin-text">
                <input type="checkbox" class="rounded-admin-input border-admin-border text-admin-primary focus:ring-admin-primary" @checked($booleanValue)>
                Boolean value placeholder
            </label>
        @elseif ($dataType === 'enum' || $dataType === 'multi_enum')
            <label for="{{ $fieldId }}-options" class="block text-sm font-semibold text-admin-text">Options placeholder</label>
            <select
                id="{{ $fieldId }}-options"
                @if ($dataType === 'multi_enum') multiple @endif
                class="mt-2 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary"
            >
                @foreach ($options as $option)
                    @php
                        $optionValue = is_array($option) ? ($option['value'] ?? $option['label'] ?? '') : (string) $option;
                        $optionLabel = is_array($option) ? ($option['label'] ?? $optionValue) : (string) $option;
                    @endphp
                    <option value="{{ $optionValue }}" @selected(in_array((string) $optionValue, $normalizedOptionValues, true))>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @elseif ($dataType === 'number')
            <label for="{{ $fieldId }}-number" class="block text-sm font-semibold text-admin-text">Numeric value placeholder</label>
            <input id="{{ $fieldId }}-number" type="number" step="any" value="{{ $normalizedValue }}" class="mt-2 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary">
        @else
            <label for="{{ $fieldId }}-text" class="block text-sm font-semibold text-admin-text">Text value placeholder</label>
            <input id="{{ $fieldId }}-text" type="text" value="{{ $normalizedValue }}" class="mt-2 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary">
        @endif
    </div>
</section>
