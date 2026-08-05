@extends('layouts.public-multi-category')

@section('title', $site->name)

@section('content')
    <div class="space-y-10" data-public-multi-shell-content>
        @if (isset($blocks) && is_iterable($blocks))
            <div data-homepage-blocks class="space-y-10" aria-label="Homepage content">
                @foreach ($blocks as $block)
                    @include($block['view'], [
                        'config' => $block['config'],
                        'data' => $block['data'],
                    ])
                @endforeach
            </div>
        @else
            <div class="rounded-foundation-card border border-foundation-border bg-foundation-surface p-6 shadow-foundation-card">
                <h1 class="text-foundation-heading font-semibold">Public catalogue foundation</h1>
                <p class="mt-2 text-foundation-body text-foundation-text-muted">Catalog content is not configured for this shell.</p>
            </div>
        @endif
    </div>
@endsection
