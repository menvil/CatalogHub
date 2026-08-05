@props(['action', 'drawerId' => 'admin-table-filters'])

<section {{ $attributes->class('rounded-admin-card border border-admin-border bg-admin-surface p-admin-card') }} data-admin-filter-bar>
    <button type="button" class="mb-3 text-sm font-semibold text-admin-primary md:hidden" data-admin-filter-open="{{ $drawerId }}" aria-controls="{{ $drawerId }}">Filters</button>
    <form method="GET" action="{{ $action }}" class="flex flex-wrap items-end gap-admin-field" id="{{ $drawerId }}" data-admin-filter-drawer hidden>
        <div class="min-w-0 flex-1">{{ $slot }}</div>
        <div class="flex gap-admin-field">
            <x-ui.button type="submit" variant="secondary">Apply filters</x-ui.button>
            <button type="button" class="text-sm text-admin-muted md:hidden" data-admin-filter-close>Close</button>
        </div>
    </form>
</section>
