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
                <article class="flex min-w-0 flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="aspect-[4/3] rounded-xl bg-slate-100" aria-hidden="true"></div>
                    <h2 class="mt-4 font-semibold"><a href="{{ $product['url'] }}" class="hover:text-blue-600">{{ $product['title'] }}</a></h2>
                    @if (($product['summary']['key_specs'] ?? []) !== [])
                        <ul class="mt-3 space-y-1 text-sm text-slate-600">
                            @foreach (array_slice($product['summary']['key_specs'], 0, 3) as $spec)
                                <li>{{ is_array($spec) ? ($spec['display_value'] ?? $spec['value'] ?? '') : $spec }}</li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="mt-8">{{ $products->links() }}</div>
    @else
        <p class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-slate-600">No projected products are available in this category yet.</p>
    @endif
@endsection
