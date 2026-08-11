@props([
    'title',
    'message',
    'clearLabel' => 'Clear filters',
    'clearUrl',
])

@php($stateId = $attributes->get('id') ?? 'filtered-empty-'.\Illuminate\Support\Str::uuid())

<section {{ $attributes->class('rounded-admin-card border border-admin-border bg-admin-surface px-admin-page py-admin-section text-center')->merge(['id' => $stateId]) }} data-ui-screen-state="filtered-empty" aria-labelledby="{{ $stateId }}-title">
    <x-ui.icon name="magnifying-glass" size="lg" class="mx-auto text-admin-muted" />
    <h3 id="{{ $stateId }}-title" class="mt-admin-card text-lg font-semibold text-admin-text">{{ $title }}</h3>
    <p class="mx-auto mt-admin-field max-w-xl text-sm text-admin-muted">{{ $message }}</p>
    <div class="mt-admin-card"><x-ui.button variant="secondary" :href="$clearUrl">{{ $clearLabel }}</x-ui.button></div>
</section>
