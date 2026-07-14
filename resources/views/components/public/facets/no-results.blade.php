@props([
    'clearUrl',
    'categoryTitle' => null,
])

<section data-filtered-no-results class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm sm:px-10">
    <span aria-hidden="true" class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-blue-50 text-3xl text-blue-500">⌕</span>
    <h2 class="mt-5 text-2xl font-bold tracking-tight text-slate-950">No products match your filters</h2>
    <p class="mx-auto mt-3 max-w-xl text-slate-600">
        Try removing a filter or broadening the selected range to see more{{ filled($categoryTitle) ? ' '.$categoryTitle : '' }} products.
    </p>
    <a href="{{ $clearUrl }}" class="mt-7 inline-flex rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white transition hover:bg-blue-500">
        Clear all filters
    </a>
</section>
