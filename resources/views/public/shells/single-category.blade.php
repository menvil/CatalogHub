@extends('layouts.public-single-category')

@section('title', $site->name)

@section('content')
    <div class="space-y-8" data-public-single-shell-content>
        @if (isset($blocks) && $blocks->isNotEmpty())
            <div data-homepage-blocks class="space-y-8" aria-label="Homepage content">
                @foreach ($blocks as $block)
                    @include($block['view'], [
                        'config' => $block['config'],
                        'data' => $block['data'],
                    ])
                @endforeach
            </div>
        @else
            <div class="rounded-foundation-card border border-foundation-border bg-foundation-surface-muted p-6">
                <h2 class="text-foundation-heading font-semibold">Focused catalogue foundation</h2>
                <p class="mt-2 text-foundation-body text-foundation-text-muted">Products and filters are not configured for this shell.</p>
            </div>
        @endif
    </div>
@endsection
