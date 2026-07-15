@props([
    'facets',
    'filters' => [],
    'action' => null,
    'sort' => null,
    'clearUrl' => null,
    'currency' => null,
    'merchants' => [],
])

<aside data-desktop-filter-sidebar class="hidden w-72 shrink-0 lg:block" aria-label="Product filters">
    <form method="get" action="{{ $action ?: url()->current() }}" data-facet-form class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-bold text-slate-950">Filters</h2>
        </div>

        @if (filled($sort))
            <input type="hidden" name="sort" value="{{ $sort }}">
        @endif

        <div class="divide-y divide-slate-200">
            <x-public.facets.fields :facets="$facets" :filters="$filters" variant="desktop" :currency="$currency" :merchants="$merchants" />
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
