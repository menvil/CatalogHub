@extends('public.layouts.app')

@section('title', $product['title'])

@section('content')
    @include('public.components.breadcrumbs', ['items' => $breadcrumbs])

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
            @include('public.components.product-rating', ['rating' => $rating])
            @if (filled($product['description'] ?? null))
                <p class="mt-6 text-lg leading-8 text-slate-600">{{ $product['description'] }}</p>
            @endif
            @if ($category)
                <p class="mt-6 text-sm text-slate-500">Category: {{ $category['label'] ?? $category['name'] ?? '' }}</p>
            @endif
        </section>
    </div>

    @include('public.components.product-benefits', ['benefits' => $benefits])

    @include('public.components.product-specs', ['sections' => $specSections])

    @if ($reviewsEnabled)
        @include('public.products.partials.reviews', ['reviews' => $reviews])

        <div class="mt-8">
            <livewire:public.reviews.review-form :site="$site" :product="$centralProductId"
                :key="'review-form-'.$site->id.'-'.$centralProductId" />
        </div>
    @endif
@endsection
