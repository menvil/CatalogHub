@props(['action', 'drawerId' => null])

@php($resolvedDrawerId = $drawerId ?: 'admin-table-filters-'.\Illuminate\Support\Str::random(8))

<section {{ $attributes->class('rounded-admin-card border border-admin-border bg-admin-surface p-admin-card') }} data-admin-filter-bar>
    <button type="button" class="mb-3 text-sm font-semibold text-admin-primary md:hidden" data-admin-filter-open="{{ $resolvedDrawerId }}" aria-controls="{{ $resolvedDrawerId }}" aria-expanded="false">Filters</button>
    <form method="GET" action="{{ $action }}" class="max-h-screen hidden flex-wrap items-end gap-admin-field overflow-y-auto md:max-h-none md:flex md:overflow-visible" id="{{ $resolvedDrawerId }}" data-admin-filter-drawer>
        <div class="min-w-0 flex-1">{{ $slot }}</div>
        <div class="flex gap-admin-field">
            <x-ui.button type="submit" variant="secondary">Apply filters</x-ui.button>
            <button type="button" class="text-sm text-admin-muted md:hidden" data-admin-filter-close>Close</button>
        </div>
    </form>
</section>
