@extends('public.layouts.app')

@section('title', 'Search')

@section('content')
    <div class="mx-auto max-w-4xl">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Search preview</p>
        <h1 class="mt-2 text-4xl font-bold tracking-tight">Search the catalogue</h1>
        <p class="mt-3 text-slate-600">Basic projected document matching is available now. Filters and autocomplete arrive in the next phase.</p>

        <form method="get" role="search" class="mt-8 flex gap-3">
            <label for="public-search" class="sr-only">Search products</label>
            <input id="public-search" name="q" value="{{ $term }}" type="search" placeholder="Search products" class="min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-4 py-3">
            <button class="rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white transition hover:bg-blue-500">Search</button>
        </form>

        <div data-search-results class="mt-8">
            @if ($term === '')
                <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-slate-600">Enter a product name or specification to start.</p>
            @elseif ($results->isEmpty())
                <p class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-slate-600">No projected products matched your search.</p>
            @else
                <p class="mb-4 text-sm text-slate-500">{{ $results->count() }} {{ Str::plural('result', $results->count()) }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($results as $result)
                        @include('public.components.product-card', ['product' => $result, 'variant' => 'list'])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
