@props(['action', 'search' => null, 'searchLabel' => 'Search', 'searchId' => null])

@php($resolvedSearchId = $searchId ?: 'admin-table-search-'.\Illuminate\Support\Str::random(8))

<form method="GET" action="{{ $action }}" role="search" {{ $attributes->class('flex flex-wrap items-end gap-admin-field') }} data-admin-table-toolbar>
    {{ $slot }}
    <div class="min-w-0 flex-1">
        <x-ui.form.input :id="$resolvedSearchId" name="q" :label="$searchLabel" :value="$search" type="search" />
    </div>
    <x-ui.button type="submit" variant="secondary">Apply</x-ui.button>
</form>
