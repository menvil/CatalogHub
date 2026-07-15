@props([
    'facets',
    'filters' => [],
    'variant' => 'desktop',
    'currency' => null,
])

@php
    $isMobile = $variant === 'mobile';
    $filterValues = $filters instanceof \App\Data\Facets\FacetFilterSet ? $filters->all() : (is_array($filters) ? $filters : []);
    $valuesFor = static function (string $code) use ($filterValues): array {
        $value = $filterValues[$code] ?? [];
        $values = is_array($value) ? $value : explode(',', (string) $value);

        return array_values(array_filter(array_map(static fn (mixed $item): string => trim((string) $item), $values)));
    };
@endphp

<details open class="group px-5 py-4">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 font-semibold text-slate-900">
        <span>Price{{ filled($currency) ? ' ('.$currency.')' : '' }}</span>
        <span aria-hidden="true" class="text-slate-400 transition group-open:rotate-180">⌄</span>
    </summary>

    <div @class(['mt-4 grid grid-cols-2', 'gap-3' => $isMobile, 'gap-2' => ! $isMobile])>
        @foreach (['price_from' => 'From', 'price_to' => 'To'] as $name => $label)
            <label class="text-sm text-slate-600">
                <span>{{ $label }}</span>
                <input
                    type="number"
                    name="{{ $name }}"
                    value="{{ $filterValues[$name] ?? '' }}"
                    min="0"
                    step="0.01"
                    @class([
                        'mt-1 w-full rounded-lg border border-slate-300 px-3 text-slate-900',
                        'py-3' => $isMobile,
                        'py-2' => ! $isMobile,
                    ])
                >
            </label>
        @endforeach
    </div>
</details>

@foreach ($facets as $facet)
    @php
        $options = collect($facet->options)->filter(
            static fn (\App\Data\Facets\FacetOptionData $option): bool => $option->count === null || $option->count > 0,
        );
        $selected = $valuesFor($facet->code);
    @endphp

    <details @if (! $facet->defaultCollapsed) open @endif class="group px-5 py-4">
        <summary @class([
            'flex cursor-pointer list-none items-center justify-between gap-3 font-semibold',
            'text-slate-900' => ! $isMobile,
        ])>
            <span>{{ $facet->label }}</span>
            <span aria-hidden="true" class="text-slate-400 transition group-open:rotate-180">⌄</span>
        </summary>

        <div class="mt-4 space-y-3">
            @if ($facet->type === \App\Enums\FacetType::Checkbox)
                @foreach ($options as $option)
                    <label @class([
                        'flex items-center justify-between gap-3 text-sm text-slate-700',
                        'min-h-11' => $isMobile,
                        'cursor-pointer' => ! $isMobile,
                    ])>
                        <span @class(['flex items-center', 'gap-3' => $isMobile, 'gap-2' => ! $isMobile])>
                            <input
                                type="checkbox"
                                name="{{ $facet->code }}[]"
                                value="{{ $option->value }}"
                                @checked(in_array($option->value, $selected, true))
                                @class([
                                    'rounded border-slate-300 text-blue-600',
                                    'size-5' => $isMobile,
                                    'size-4 focus:ring-blue-500' => ! $isMobile,
                                ])
                            >
                            <span>{{ $option->label }}</span>
                        </span>
                        @if ($option->count !== null)
                            <span class="text-xs tabular-nums text-slate-400">{{ $option->count }}</span>
                        @endif
                    </label>
                @endforeach
            @elseif ($facet->type === \App\Enums\FacetType::Range)
                @if ($facet->sourceType === \App\Enums\FacetSourceType::Rating)
                    <label class="block text-sm text-slate-600">
                        <span>Minimum rating</span>
                        <input
                            type="number"
                            name="rating_min"
                            value="{{ $filterValues['rating_min'] ?? '' }}"
                            min="0"
                            max="5"
                            step="0.5"
                            @class([
                                'mt-1 w-full rounded-lg border border-slate-300 px-3 text-slate-900',
                                'py-3' => $isMobile,
                                'py-2' => ! $isMobile,
                            ])
                        >
                    </label>
                @else
                    <div @class(['grid grid-cols-2', 'gap-3' => $isMobile, 'gap-2' => ! $isMobile])>
                        @foreach (['min' => 'Min', 'max' => 'Max'] as $bound => $label)
                            <label class="text-sm text-slate-600">
                                <span>{{ $label }}</span>
                                <input
                                    type="number"
                                    name="{{ $facet->code }}_{{ $bound }}"
                                    value="{{ $filterValues[$facet->code.'_'.$bound] ?? '' }}"
                                    @if (isset($facet->config['min'])) min="{{ $facet->config['min'] }}" @endif
                                    @if (isset($facet->config['max'])) max="{{ $facet->config['max'] }}" @endif
                                    @if (isset($facet->config['step'])) step="{{ $facet->config['step'] }}" @endif
                                    @class([
                                        'mt-1 w-full rounded-lg border border-slate-300 px-3 text-slate-900',
                                        'py-3' => $isMobile,
                                        'py-2' => ! $isMobile,
                                    ])
                                >
                            </label>
                        @endforeach
                    </div>
                    @if (! $isMobile && filled($facet->config['unit_code'] ?? null))
                        <p class="text-xs text-slate-500">Values in {{ $facet->config['unit_code'] }}</p>
                    @endif
                @endif
            @elseif ($facet->type === \App\Enums\FacetType::Boolean)
                <label @class([
                    'flex items-center text-sm text-slate-700',
                    'min-h-11 gap-3' => $isMobile,
                    'cursor-pointer gap-2' => ! $isMobile,
                ])>
                    <input
                        type="checkbox"
                        name="{{ $facet->code }}"
                        value="1"
                        @checked(app(\App\Support\Facets\BooleanFacetValueParser::class)->parse($filterValues[$facet->code] ?? null) === true)
                        @class([
                            'rounded border-slate-300 text-blue-600',
                            'size-5' => $isMobile,
                            'size-4 focus:ring-blue-500' => ! $isMobile,
                        ])
                    >
                    <span>Yes</span>
                </label>
            @elseif ($facet->type === \App\Enums\FacetType::Select)
                <select
                    name="{{ $facet->code }}"
                    @class([
                        'w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900',
                        'py-3' => $isMobile,
                        'py-2' => ! $isMobile,
                    ])
                >
                    <option value="">{{ $facet->config['placeholder'] ?? 'Any' }}</option>
                    @foreach ($options as $option)
                        <option value="{{ $option->value }}" @selected(in_array($option->value, $selected, true))>
                            {{ $option->label }}@if ($option->count !== null) ({{ $option->count }})@endif
                        </option>
                    @endforeach
                </select>
            @endif
        </div>
    </details>
@endforeach
