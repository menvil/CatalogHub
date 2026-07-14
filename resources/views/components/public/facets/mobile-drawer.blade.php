@props([
    'facets',
    'filters' => [],
    'action' => null,
    'sort' => null,
    'id' => 'mobile-filter-drawer',
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

<div data-mobile-filter-drawer class="lg:hidden">
    <button
        type="button"
        data-filter-drawer-open
        aria-controls="{{ $id }}"
        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 font-semibold text-slate-800 shadow-sm"
    >
        <span aria-hidden="true">☷</span>
        Filters
    </button>

    <dialog
        id="{{ $id }}"
        data-filter-drawer
        aria-label="Product filters"
        class="m-0 ml-auto h-dvh max-h-dvh w-[min(92vw,26rem)] max-w-none overflow-hidden bg-white p-0 text-slate-950 shadow-2xl backdrop:bg-slate-950/50"
    >
        <form method="get" action="{{ $action ?: url()->current() }}" data-facet-form class="flex h-full flex-col">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 class="text-xl font-bold">Filters</h2>
                <button
                    type="button"
                    data-filter-drawer-close
                    aria-label="Close filters"
                    class="rounded-lg p-2 text-xl text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                >
                    <span aria-hidden="true">×</span>
                </button>
            </div>

            @if (filled($sort))
                <input type="hidden" name="sort" value="{{ $sort }}">
            @endif

            <div class="min-h-0 flex-1 divide-y divide-slate-200 overflow-y-auto">
                @foreach ($facets as $facet)
                    @php
                        $options = collect($facet->options)->filter(
                            static fn (\App\Data\Facets\FacetOptionData $option): bool => $option->count === null || $option->count > 0,
                        );
                        $selected = $valuesFor($facet->code);
                    @endphp

                    <details @if (! $facet->defaultCollapsed) open @endif class="group px-5 py-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 font-semibold">
                            <span>{{ $facet->label }}</span>
                            <span aria-hidden="true" class="text-slate-400 transition group-open:rotate-180">⌄</span>
                        </summary>
                        <div class="mt-4 space-y-3">
                            @if ($facet->type === \App\Enums\FacetType::Checkbox)
                                @foreach ($options as $option)
                                    <label class="flex min-h-11 items-center justify-between gap-3 text-sm text-slate-700">
                                        <span class="flex items-center gap-3">
                                            <input type="checkbox" name="{{ $facet->code }}[]" value="{{ $option->value }}" @checked(in_array($option->value, $selected, true)) class="size-5 rounded border-slate-300 text-blue-600">
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
                                        <input type="number" name="rating_min" value="{{ $filterValues['rating_min'] ?? '' }}" min="0" max="5" step="0.5" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-3 text-slate-900">
                                    </label>
                                @else
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="text-sm text-slate-600">
                                            <span>Min</span>
                                            <input type="number" name="{{ $facet->code }}_min" value="{{ $filterValues[$facet->code.'_min'] ?? '' }}" @if (isset($facet->config['min'])) min="{{ $facet->config['min'] }}" @endif @if (isset($facet->config['max'])) max="{{ $facet->config['max'] }}" @endif @if (isset($facet->config['step'])) step="{{ $facet->config['step'] }}" @endif class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-3 text-slate-900">
                                        </label>
                                        <label class="text-sm text-slate-600">
                                            <span>Max</span>
                                            <input type="number" name="{{ $facet->code }}_max" value="{{ $filterValues[$facet->code.'_max'] ?? '' }}" @if (isset($facet->config['min'])) min="{{ $facet->config['min'] }}" @endif @if (isset($facet->config['max'])) max="{{ $facet->config['max'] }}" @endif @if (isset($facet->config['step'])) step="{{ $facet->config['step'] }}" @endif class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-3 text-slate-900">
                                        </label>
                                    </div>
                                @endif
                            @elseif ($facet->type === \App\Enums\FacetType::Boolean)
                                <label class="flex min-h-11 items-center gap-3 text-sm text-slate-700">
                                    <input type="checkbox" name="{{ $facet->code }}" value="1" @checked(app(\App\Support\Facets\BooleanFacetValueParser::class)->parse($filterValues[$facet->code] ?? null) === true) class="size-5 rounded border-slate-300 text-blue-600">
                                    <span>Yes</span>
                                </label>
                            @elseif ($facet->type === \App\Enums\FacetType::Select)
                                <select name="{{ $facet->code }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900">
                                    <option value="">{{ $facet->config['placeholder'] ?? 'Any' }}</option>
                                    @foreach ($options as $option)
                                        <option value="{{ $option->value }}" @selected(in_array($option->value, $selected, true))>{{ $option->label }}@if ($option->count !== null) ({{ $option->count }})@endif</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>

            <div class="sticky bottom-0 grid grid-cols-2 gap-3 border-t border-slate-200 bg-white p-4 shadow-[0_-8px_24px_rgba(15,23,42,0.08)]">
                <a href="{{ $clearUrl ?: app(\App\Support\Facets\FacetUrlBuilder::class)->clearAll($action ?: url()->current()) }}" class="rounded-xl border border-slate-300 px-4 py-3 text-center font-semibold text-slate-700">Clear filters</a>
                <button type="submit" class="rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white">Apply filters</button>
            </div>
        </form>
    </dialog>
</div>
