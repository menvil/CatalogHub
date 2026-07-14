@extends('public.layouts.app')

@section('title', $site->name)

@section('content')
    <section class="overflow-hidden rounded-3xl bg-slate-950 px-6 py-14 text-white sm:px-10 lg:px-16 lg:py-20">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-300">{{ $site->name }}</p>
        <h1 class="mt-4 max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">
            {{ $hero['title'] ?? 'Find products worth comparing' }}
        </h1>
        @if (filled($hero['subtitle'] ?? null))
            <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-300">{{ $hero['subtitle'] }}</p>
        @endif
    </section>

    <div data-homepage-blocks class="mt-10 space-y-10" aria-label="Homepage content"></div>
@endsection
