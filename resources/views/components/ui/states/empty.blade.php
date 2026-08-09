@props([
    'title',
    'message',
    'actionLabel' => null,
    'actionUrl' => null,
    'icon' => 'inbox-stack',
])

@php
    throw_if(filled($actionLabel) !== filled($actionUrl), \InvalidArgumentException::class, 'Empty-state actions require both a label and URL.');
@endphp

<section {{ $attributes->class('rounded-admin-card border border-dashed border-admin-border bg-admin-surface px-admin-page py-admin-section text-center') }} data-ui-screen-state="empty" aria-labelledby="{{ $attributes->get('id', 'initial-empty') }}-title">
    <x-ui.icon :name="$icon" size="lg" class="mx-auto text-admin-muted" />
    <h3 id="{{ $attributes->get('id', 'initial-empty') }}-title" class="mt-admin-card text-lg font-semibold text-admin-text">{{ $title }}</h3>
    <p class="mx-auto mt-admin-field max-w-xl text-sm text-admin-muted">{{ $message }}</p>
    @if (filled($actionLabel))
        <div class="mt-admin-card"><x-ui.button :href="$actionUrl">{{ $actionLabel }}</x-ui.button></div>
    @endif
</section>
