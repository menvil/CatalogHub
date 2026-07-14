@extends('public.layouts.app')

@section('title', $article['title'])

@section('content')
    <article class="mx-auto max-w-3xl">
        @include('public.components.breadcrumbs', ['items' => $breadcrumbs])
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Article preview</p>
        <h1 class="mt-3 text-4xl font-bold tracking-tight sm:text-5xl">{{ $article['title'] }}</h1>
        <p class="mt-5 text-xl leading-8 text-slate-600">{{ $article['excerpt'] }}</p>

        <div class="mt-10 space-y-6 text-lg leading-8 text-slate-700">
            @foreach ($article['body'] as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>

        <aside class="mt-12 rounded-2xl border border-dashed border-slate-300 bg-white p-6">
            <h2 class="text-lg font-semibold">Related products</h2>
            <p class="mt-2 text-slate-600">Related products are coming soon with the Content Engine.</p>
        </aside>
    </article>
@endsection
