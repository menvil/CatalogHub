@props([
    'facets',
    'filters' => [],
    'action' => null,
    'sort' => null,
    'clearUrl' => null,
])

@php
    $filterValues = $filters instanceof \App\Data\Facets\FacetFilterSet ? $filters->all() : (is_array($filters) ? $filters : []);
    $valuesFor = static function (string $code) use ($filterValues): array {
        $value = $filterValues[$code] ?? [];
        $values = is_array($value) ? $value : explode(',', (string) $value);

        return array_values(array_filter(array_map(static fn (mixed $item): string => trim((string) $item), $values)));
    };
@endphp

<aside data-desktop-filter-sidebar class="hidden w-72 shrink-0 lg:block" aria-label="Product filters">
    <form method="get" action="{{ $action ?: url()->current() }}" data-facet-form class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-bold text-slate-950">Filters</h2>
        </div>

        @if (filled($sort))
            <input type="hidden" name="sort" value="{{ $sort }}">
        @endif

        <div class="divide-y divide-slate-200">
            @foreach ($facets as $facet)
                @php
                    $options = collect($facet->options)->filter(
                        static fn (\App\Data\Facets\FacetOptionData $option): bool => $option->count === null || $option->count > 0,
                    );
                    $selected = $valuesFor($facet->code);
                @endphp

                <details @if (! $facet->defaultCollapsed) open @endif class="group px-5 py-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 font-semibold text-slate-900">
                        <span>{{ $facet->label }}</span>
                        <span aria-hidden="true" class="text-slate-400 transition group-open:rotate-180">⌄</span>
                    </summary>

                    <div class="mt-4 space-y-3">
                        @if ($facet->type === \App\Enums\FacetType::Checkbox)
                            @foreach ($options as $option)
                                <label class="flex cursor-pointer items-center justify-between gap-3 text-sm text-slate-700">
                                    <span class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            name="{{ $facet->code }}[]"
                                            value="{{ $option->value }}"
                                            @checked(in_array($option->value, $selected, true))
                                            class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
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
                                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900"
                                    >
                                </label>
                            @else
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="text-sm text-slate-600">
                                        <span>Min</span>
                                        <input
                                            type="number"
                                            name="{{ $facet->code }}_min"
                                            value="{{ $filterValues[$facet->code.'_min'] ?? '' }}"
                                            @if (isset($facet->config['min'])) min="{{ $facet->config['min'] }}" @endif
                                            @if (isset($facet->config['max'])) max="{{ $facet->config['max'] }}" @endif
                                            @if (isset($facet->config['step'])) step="{{ $facet->config['step'] }}" @endif
                                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900"
                                        >
                                    </label>
                                    <label class="text-sm text-slate-600">
                                        <span>Max</span>
                                        <input
                                            type="number"
                                            name="{{ $facet->code }}_max"
                                            value="{{ $filterValues[$facet->code.'_max'] ?? '' }}"
                                            @if (isset($facet->config['min'])) min="{{ $facet->config['min'] }}" @endif
                                            @if (isset($facet->config['max'])) max="{{ $facet->config['max'] }}" @endif
                                            @if (isset($facet->config['step'])) step="{{ $facet->config['step'] }}" @endif
                                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-900"
                                        >
                                    </label>
                                </div>
                                @if (filled($facet->config['unit_code'] ?? null))
                                    <p class="text-xs text-slate-500">Values in {{ $facet->config['unit_code'] }}</p>
                                @endif
                            @endif
                        @elseif ($facet->type === \App\Enums\FacetType::Boolean)
                            <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    name="{{ $facet->code }}"
                                    value="1"
                                    @checked(app(\App\Support\Facets\BooleanFacetValueParser::class)->parse($filterValues[$facet->code] ?? null) === true)
                                    class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                >
                                <span>Yes</span>
                            </label>
                        @elseif ($facet->type === \App\Enums\FacetType::Select)
                            <select name="{{ $facet->code }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
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
        </div>

        <div class="grid grid-cols-2 gap-3 border-t border-slate-200 p-4">
            <a href="{{ $clearUrl ?: app(\App\Support\Facets\FacetUrlBuilder::class)->clearAll($action ?: url()->current()) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-center font-semibold text-slate-700 transition hover:bg-slate-50">
                Clear filters
            </a>
            <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2.5 font-semibold text-white transition hover:bg-blue-500">
                Apply filters
            </button>
        </div>
    </form>
</aside>
