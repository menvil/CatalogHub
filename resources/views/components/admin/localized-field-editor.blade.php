@props([
    'fieldName',
    'locales' => [],
    'values' => [],
    'statuses' => [],
    'mode' => 'tabs',
])

@php
    $isTabs = $mode === 'tabs';
    $normalizedLocales = collect($locales)->map(function ($locale) {
        if (is_array($locale)) {
            return [
                'code' => $locale['code'] ?? $locale['locale'] ?? '',
                'label' => $locale['label'] ?? $locale['code'] ?? $locale['locale'] ?? '',
            ];
        }

        return ['code' => (string) $locale, 'label' => strtoupper((string) $locale)];
    })->values()->all();
    $fieldId = \Illuminate\Support\Str::slug($fieldName) ?: 'localized-field';
@endphp

<section
    {{ $attributes->class('rounded-admin-card border border-admin-border bg-admin-surface p-admin-card') }}
    data-admin-localized-field-editor="{{ $mode }}"
>
    <div class="flex flex-col gap-admin-field md:flex-row md:items-start md:justify-between">
        <div>
            <h2 class="text-base font-semibold text-admin-text">{{ $fieldName }}</h2>
            <p class="mt-1 text-sm text-admin-muted">Localized field shell</p>
        </div>

        <x-admin.status-badge label="{{ count($normalizedLocales) }} locales" variant="neutral" size="sm" />
    </div>

    @if ($isTabs)
        <div class="mt-4 flex gap-1 overflow-x-auto border-b border-admin-border" role="tablist" aria-label="{{ $fieldName }} locales">
            @foreach ($normalizedLocales as $index => $locale)
                @php
                    $status = $statuses[$locale['code']] ?? 'missing';
                    $isActive = $index === 0;
                @endphp

                <button
                    type="button"
                    role="tab"
                    aria-selected="{{ $isActive ? 'true' : 'false' }}"
                    class="@class([
                        'flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium',
                        'border-admin-primary text-admin-primary' => $isActive,
                        'border-transparent text-admin-muted' => ! $isActive,
                    ])"
                >
                    <span>{{ $locale['label'] }}</span>
                    <x-admin.translation-status-badge :status="$status" :locale="$locale['code']" />
                </button>
            @endforeach
        </div>
    @endif

    <div class="mt-4 grid gap-admin-field {{ $isTabs ? '' : 'md:grid-cols-2' }}">
        @foreach ($normalizedLocales as $locale)
            @php
                $value = $values[$locale['code']] ?? '';
                $status = $statuses[$locale['code']] ?? 'missing';
                $inputId = 'localized-'.$fieldId.'-'.$locale['code'];
            @endphp

            <div class="rounded-admin-input border border-admin-border bg-admin-surface-muted p-3">
                <div class="flex flex-wrap items-center justify-between gap-admin-field">
                    <label for="{{ $inputId }}" class="text-sm font-semibold text-admin-text">{{ $locale['label'] }}</label>
                    <x-admin.translation-status-badge :status="$status" :locale="$locale['code']" />
                </div>

                <textarea
                    id="{{ $inputId }}"
                    rows="3"
                    class="mt-3 w-full resize-y rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text placeholder:text-admin-muted focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary"
                    placeholder="Missing {{ $fieldName }} value"
                >{{ $value }}</textarea>

                @if ($value === '')
                    <p class="mt-2 text-xs text-admin-muted">Missing localized value</p>
                @endif
            </div>
        @endforeach
    </div>
</section>
