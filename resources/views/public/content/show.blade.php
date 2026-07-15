@extends('public.layouts.app')

@section('title', $translation->seoTitle())

@section('content')
    <article class="mx-auto max-w-3xl">
        @include('public.components.breadcrumbs', ['items' => $breadcrumbs])

        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">
            {{ $contentItem->type->label() }}
        </p>
        <h1 class="mt-3 text-4xl font-bold tracking-tight sm:text-5xl">{{ $translation->title }}</h1>

        @if (filled($translation->excerpt))
            <p class="mt-5 text-xl leading-8 text-slate-600">{{ $translation->excerpt }}</p>
        @endif

        @if ($contentItem->published_at)
            <time class="mt-4 block text-sm text-slate-500" datetime="{{ $contentItem->published_at->toAtomString() }}">
                {{ $contentItem->published_at->toFormattedDateString() }}
            </time>
        @endif

        @if ($contentItem->type === \App\Enums\ContentType::Faq)
            @include('public.content.partials.faq', ['items' => $translation->body_json ?? []])
        @elseif (filled($translation->body))
            <div class="mt-10 whitespace-pre-line text-lg leading-8 text-slate-700">{{ $translation->body }}</div>
        @endif
    </article>
@endsection
