@extends('public.layouts.app')

@section('title', $category['title'])

@section('content')
    @include('public.components.breadcrumbs', ['items' => $breadcrumbs])

    <section class="mt-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-10">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Category</p>
        <h1 class="mt-3 text-4xl font-bold tracking-tight">{{ $category['title'] }}</h1>
        @if (filled($category['description']))
            <p class="mt-5 max-w-3xl text-lg leading-8 text-slate-600">{{ $category['description'] }}</p>
        @endif
        @if (filled($category['intro']))
            <p class="mt-4 max-w-3xl text-slate-600">{{ $category['intro'] }}</p>
        @endif
        <a href="{{ $listingUrl }}" class="mt-8 inline-flex rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white transition hover:bg-blue-500">
            Browse products
        </a>
    </section>
@endsection
