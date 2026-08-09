@props(['id', 'title', 'open' => false, 'contained' => false])

@php
    throw_if(trim((string) $id) === '', \InvalidArgumentException::class, 'Modal IDs cannot be empty.');
    $titleId = $id.'-title';
@endphp

<div
    id="{{ $id }}"
    {{ $attributes->class(['inset-0 z-50 flex items-center justify-center p-admin-page', 'absolute' => $contained, 'fixed' => ! $contained, 'hidden' => ! $open]) }}
    data-admin-modal="{{ $id }}"
    data-admin-modal-open="{{ $open ? 'true' : 'false' }}"
    data-admin-modal-contained="{{ $contained ? 'true' : 'false' }}"
>
    <button type="button" class="absolute inset-0 bg-admin-text/35" data-admin-modal-close aria-label="Close {{ $title }}"></button>
    <section class="relative w-full max-w-lg rounded-admin-modal border border-admin-border bg-admin-surface shadow-admin-modal" role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}">
        <header class="flex items-start justify-between gap-admin-field border-b border-admin-border p-admin-card">
            <h2 id="{{ $titleId }}" class="text-lg font-semibold text-admin-text">{{ $title }}</h2>
            <button type="button" class="text-admin-muted" data-admin-modal-close aria-label="Close {{ $title }}">×</button>
        </header>
        <div class="p-admin-card text-sm text-admin-text">{{ $slot }}</div>
        @isset($footer)
            <footer class="border-t border-admin-border bg-admin-surface-muted p-admin-card">{{ $footer }}</footer>
        @endisset
    </section>
</div>
