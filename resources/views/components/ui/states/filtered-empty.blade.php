@props([
    'title',
    'message',
    'clearLabel' => 'Clear filters',
    'clearUrl',
])

<section {{ $attributes->class('rounded-admin-card border border-admin-border bg-admin-surface px-admin-page py-admin-section text-center') }} data-ui-screen-state="filtered-empty" aria-labelledby="{{ $attributes->get('id', 'filtered-empty') }}-title">
    <x-ui.icon name="magnifying-glass" size="lg" class="mx-auto text-admin-muted" />
    <h3 id="{{ $attributes->get('id', 'filtered-empty') }}-title" class="mt-admin-card text-lg font-semibold text-admin-text">{{ $title }}</h3>
    <p class="mx-auto mt-admin-field max-w-xl text-sm text-admin-muted">{{ $message }}</p>
    <div class="mt-admin-card"><x-ui.button variant="secondary" :href="$clearUrl">{{ $clearLabel }}</x-ui.button></div>
</section>
