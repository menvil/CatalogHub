@props([
    'filters',
    'baseUrl' => null,
    'query' => null,
])

@if (count($filters) > 0)
    @php
        $urlBuilder = app(\App\Support\Facets\FacetUrlBuilder::class);
        $resolvedBaseUrl = $baseUrl ?: url()->current();
        $resolvedQuery = is_array($query) ? $query : request()->query();
    @endphp

    <div data-active-filters class="flex flex-wrap items-center gap-2" aria-label="Active filters">
        @foreach ($filters as $filter)
            <a
                href="{{ $urlBuilder->removeAppliedFilter($resolvedBaseUrl, $resolvedQuery, $filter) }}"
                class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-800 ring-1 ring-inset ring-blue-200 transition hover:bg-blue-100"
                aria-label="Remove {{ $filter->label }} filter"
            >
                <span>{{ $filter->label }}</span>
                <span aria-hidden="true">×</span>
            </a>
        @endforeach

        <a href="{{ $urlBuilder->clearAll($resolvedBaseUrl) }}" class="px-2 py-1.5 text-sm font-semibold text-slate-600 underline-offset-4 hover:text-slate-950 hover:underline">
            Clear all
        </a>
    </div>
@endif
