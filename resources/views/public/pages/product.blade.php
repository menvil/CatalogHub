@extends('public.layouts.app')

@section('title', $product['title'])

@section('content')
    <div class="text-sm text-slate-500">
        <a href="/{{ $locale }}" class="hover:text-slate-950">Home</a>
        @if ($category)
            <span aria-hidden="true" class="px-2">/</span>
            <a href="/{{ $locale }}/categories/{{ $category['slug'] }}" class="hover:text-slate-950">{{ $category['label'] ?? $category['name'] ?? 'Category' }}</a>
        @endif
    </div>

    <div class="mt-6 grid gap-8 lg:grid-cols-2">
        @include('public.components.product-media-gallery', ['media' => $media])

        <section class="py-2">
            @if ($brand)
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">{{ $brand['name'] ?? '' }}</p>
            @endif
            <h1 class="mt-3 text-4xl font-bold tracking-tight sm:text-5xl">{{ $product['title'] }}</h1>
            @if (filled($product['model'] ?? null))
                <p class="mt-3 text-sm text-slate-500">Model {{ $product['model'] }}</p>
            @endif
            @if (filled($product['description'] ?? null))
                <p class="mt-6 text-lg leading-8 text-slate-600">{{ $product['description'] }}</p>
            @endif
            @if ($category)
                <p class="mt-6 text-sm text-slate-500">Category: {{ $category['label'] ?? $category['name'] ?? '' }}</p>
            @endif
        </section>
    </div>

    <section data-product-specs class="mt-12 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-2xl font-bold tracking-tight">Specifications</h2>
        <p class="mt-2 text-slate-600">{{ count($specSections) }} projected {{ Str::plural('section', count($specSections)) }}</p>
    </section>
@endsection
