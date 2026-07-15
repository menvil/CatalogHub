@extends('public.layouts.app')

@section('title', $category['title'].' products')

@section('content')
    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Product listing</p>
            <h1 class="mt-2 text-4xl font-bold tracking-tight">{{ $category['title'] }}</h1>
            <p class="mt-3 text-slate-600">{{ $products->total() }} projected {{ Str::plural('product', $products->total()) }}</p>
        </div>

        <div class="flex items-center gap-3">
            <x-public.facets.mobile-drawer
                :facets="$facets"
                :filters="$filters"
                :action="$listingUrl"
                :sort="$sort"
                :clear-url="$clearFiltersUrl"
                :currency="$currency"
                :merchants="$merchants"
            />

            <form method="get" action="{{ $listingUrl }}" data-facet-form class="flex items-center gap-3">
                @foreach (collect($filters->toQueryArray())->except('sort') as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <label for="listing-sort" class="text-sm font-medium">Sort by</label>
                <select id="listing-sort" name="sort" class="rounded-lg border border-slate-300 bg-white px-3 py-2" onchange="this.form.submit()">
                    @foreach ($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @if ($appliedFilters !== [])
        <div class="mt-6">
            <x-public.facets.active-filters :filters="$appliedFilters" :base-url="$listingUrl" :query="request()->query()" />
        </div>
    @endif

    <div class="mt-8 flex items-start gap-8">
        <x-public.facets.desktop-sidebar
            :facets="$facets"
            :filters="$filters"
            :action="$listingUrl"
            :sort="$sort"
            :clear-url="$clearFiltersUrl"
            :currency="$currency"
            :merchants="$merchants"
        />

        <div class="min-w-0 flex-1">
            @if ($products->isNotEmpty())
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($products as $product)
                        @include('public.components.product-card', ['product' => $product, 'variant' => 'grid'])
                    @endforeach
                </div>

                <div class="mt-8">{{ $products->links('pagination::tailwind') }}</div>
            @else
                @if ($appliedFilters !== [])
                    <x-public.facets.no-results :clear-url="$clearFiltersUrl" :category-title="$category['title']" />
                @else
                    @include('public.components.empty-state', [
                        'title' => 'No products here yet',
                        'message' => 'No projected products are available in this category yet.',
                        'actionUrl' => $categoryUrl,
                        'actionLabel' => 'Back to '.$category['title'],
                    ])
                @endif
            @endif
        </div>
    </div>
@endsection
