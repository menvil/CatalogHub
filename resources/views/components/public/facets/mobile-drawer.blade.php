@props([
    'facets',
    'filters' => [],
    'action' => null,
    'sort' => null,
    'id' => 'mobile-filter-drawer',
    'clearUrl' => null,
    'currency' => null,
])

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
                <x-public.facets.fields :facets="$facets" :filters="$filters" variant="mobile" :currency="$currency" />
            </div>

            <div class="sticky bottom-0 grid grid-cols-2 gap-3 border-t border-slate-200 bg-white p-4 shadow-[0_-8px_24px_rgba(15,23,42,0.08)]">
                <a href="{{ $clearUrl ?: app(\App\Support\Facets\FacetUrlBuilder::class)->clearAll($action ?: url()->current()) }}" class="rounded-xl border border-slate-300 px-4 py-3 text-center font-semibold text-slate-700">Clear filters</a>
                <button type="submit" class="rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white">Apply filters</button>
            </div>
        </form>
    </dialog>
</div>
