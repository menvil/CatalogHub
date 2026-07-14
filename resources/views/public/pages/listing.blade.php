@extends('public.layouts.app')

@section('title', $category['title'].' products')

@section('content')
    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Product listing</p>
            <h1 class="mt-2 text-4xl font-bold tracking-tight">{{ $category['title'] }}</h1>
            <p class="mt-3 text-slate-600">{{ $products->total() }} projected {{ Str::plural('product', $products->total()) }}</p>
        </div>
        <form method="get" class="flex items-center gap-3">
            <label for="listing-sort" class="text-sm font-medium">Sort by</label>
            <select id="listing-sort" name="sort" class="rounded-lg border border-slate-300 bg-white px-3 py-2" onchange="this.form.submit()">
                <option value="latest" @selected($sort === 'latest')>Latest</option>
                <option value="title" @selected($sort === 'title')>Title</option>
            </select>
        </form>
    </div>

    @if ($products->isNotEmpty())
        <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($products as $product)
                @include('public.components.product-card', ['product' => $product, 'variant' => 'grid'])
            @endforeach
        </div>

        <div class="mt-8">{{ $products->links() }}</div>
    @else
        <p class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-slate-600">No projected products are available in this category yet.</p>
    @endif
@endsection
