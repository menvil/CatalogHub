@props(['filters' => [], 'clearAllUrl'])

@php
    throw_unless(
        \App\Support\Presentation\SafePresentationUrl::allows($clearAllUrl, allowQuery: true),
        \InvalidArgumentException::class,
        'Active filters require a safe clear-all URL.',
    );
@endphp

@if ($filters !== [])
    <nav {{ $attributes->class('flex flex-wrap items-center gap-2') }} aria-label="Active filters" data-admin-active-filters>
        @foreach ($filters as $filter)
            @php
                throw_unless(\App\Support\Presentation\SafePresentationUrl::allows($filter['removeUrl'] ?? null, allowQuery: true), \InvalidArgumentException::class, 'Active filters require safe removal URLs.');
                $filterLabel = $filter['label'] ?? $filter['key'] ?? 'Filter';
            @endphp
            <a href="{{ $filter['removeUrl'] }}" class="rounded-full border border-admin-border bg-admin-surface-muted px-3 py-1 text-sm text-admin-text" aria-label="Remove filter: {{ $filterLabel }}">
                {{ $filterLabel }} <span aria-hidden="true">×</span>
            </a>
        @endforeach
        <a href="{{ $clearAllUrl }}" class="text-sm font-semibold text-admin-primary" data-admin-clear-all-filters>Clear all</a>
    </nav>
@endif
