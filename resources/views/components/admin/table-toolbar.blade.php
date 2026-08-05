@props(['action', 'search' => null, 'searchLabel' => 'Search'])

<form method="GET" action="{{ $action }}" {{ $attributes->class('flex flex-wrap items-end gap-admin-field') }} data-admin-table-toolbar>
    <x-ui.form.input name="q" :label="$searchLabel" :value="$search" type="search" />
    <x-ui.button type="submit" variant="secondary">Apply</x-ui.button>
</form>
