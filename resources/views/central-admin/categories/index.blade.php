@extends('layouts.central-admin', ['activeNav' => 'categories', 'pageTitle' => 'Categories'])

@section('breadcrumbs')
    <a href="{{ route('filament.central.pages.home', absolute: false) }}">Dashboard</a>
    <span aria-hidden="true">/</span><span aria-current="page">Categories</span>
@endsection

@section('content')
<div class="category-list-page" data-screen-id="CA-016" data-fixture-version="categories-list-v1">
    <header class="category-list-heading">
        <div><h1 class="text-foundation-heading font-semibold text-admin-text">Categories</h1><p>Organize your catalog hierarchy and inspect category schemas, usage, and translations.</p></div>
        @if ($createUrl)<x-ui.button :href="$createUrl">New Category</x-ui.button>@endif
    </header>
    <section class="category-list-metrics" aria-label="Category summary">
        @foreach ($metrics as $metric)
            <article class="category-list-metric category-list-metric--{{ $metric['tone'] }}" data-category-metric="{{ $metric['key'] }}">
                <div class="category-list-metric-label"><span class="category-list-metric-icon"><x-ui.icon :name="$metric['icon']" size="sm" /></span>{{ $metric['label'] }}</div>
                <strong>{{ number_format($metric['value']) }}</strong><span>{{ $metric['detail'] }}</span>
            </article>
        @endforeach
    </section>
    @if (session('success'))<p role="status" class="text-admin-success">{{ session('success') }}</p>@endif
    @if ($errors->any())<p role="alert" class="text-admin-danger">{{ $errors->first() }}</p>@endif
    <section class="category-list-surface" aria-label="Categories list">
        <form method="GET" action="{{ route('central.categories.index') }}" class="category-list-filters">
            <div class="category-list-search"><label for="category-search">Search</label><div><x-ui.icon name="magnifying-glass" /><input id="category-search" type="search" name="q" value="{{ $filters->search }}" placeholder="Search by name or slug…" data-category-list-search></div></div>
            <x-ui.form.select id="category-level" name="level" label="Level" placeholder="All levels" :options="$list->levelOptions" :selected="$filters->level" />
            <x-ui.form.select id="category-status" name="status" label="Category status" placeholder="All statuses" :options="$statusOptions" :selected="$filters->status" />
            <x-ui.form.select id="category-schema" name="schema" label="Schema status" placeholder="All schema states" :options="$schemaOptions" :selected="$filters->schemaStatus" />
            <x-ui.form.select id="category-site" name="site" label="Selected by Site" placeholder="All Sites" :options="$list->siteOptions" :selected="$filters->siteId" />
            <x-ui.form.select id="category-locale" name="locale" label="Locale" placeholder="All active Locales" :options="$list->localeOptions" :selected="$filters->localeId" />
            <x-ui.form.select id="category-translation" name="translation" label="Translation" placeholder="All states" :options="['covered' => 'Covered', 'missing' => 'Missing', 'outdated' => 'Outdated']" :selected="$filters->translation" />
            @include('central-admin.categories._filter-inputs', ['excludedFilters' => ['q', 'level', 'status', 'schema', 'site', 'locale', 'translation']])
            <button type="submit" class="sr-only">Apply filters</button>
            @if ($filters->hasConstraints())
                <div class="category-list-active-filters" data-category-active-filter-count="{{ $filters->activeCount() }}"><span>{{ $filters->activeCount() }} active filters</span><x-ui.button variant="secondary" :href="$clearFiltersUrl" aria-label="Clear filters">Clear filters</x-ui.button></div>
            @endif
        </form>
        <form method="GET" action="{{ route('central.categories.index') }}" class="category-list-mobile-sort">
            @include('central-admin.categories._filter-inputs', ['excludedFilters' => ['sort', 'direction']])
            <x-ui.form.select id="category-sort" name="sort" label="Sort by" :options="$sortLabels" :selected="$filters->sort" />
            <x-ui.form.select id="category-direction" name="direction" label="Sort direction" :options="['asc' => 'Ascending', 'desc' => 'Descending']" :selected="$filters->direction" />
        </form>
        <div class="category-list-table-wrap" data-admin-data-table>
            <table class="category-list-table">
                <caption class="sr-only">Categories with direct usage, separate lifecycle and schema states, and active-Locale translations</caption>
                <thead><tr>
                    @foreach (['name', 'products', 'attributes', 'facets', 'status', 'schema_status', 'sites'] as $column)
                        <th scope="col" aria-sort="{{ $filters->sort === $column ? ($filters->direction === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a href="{{ $sortUrls[$column] }}">{{ $sortLabels[$column] }} <span aria-hidden="true">{{ $filters->sort === $column ? ($filters->direction === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th>
                    @endforeach
                    <th scope="col">Translations</th>
                    <th scope="col" aria-sort="{{ $filters->sort === 'updated_at' ? ($filters->direction === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a href="{{ $sortUrls['updated_at'] }}">Updated <span aria-hidden="true">{{ $filters->sort === 'updated_at' ? ($filters->direction === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th>
                    <th scope="col">Actions</th>
                </tr></thead>
                <tbody>
                    @forelse ($list->categories as $row)
                        <tr data-row-id="{{ $row->category->id }}">
                            <td class="category-list-identity-cell"><div class="category-list-identity"><span class="category-list-icon"><x-ui.icon name="squares-2x2" /></span><div><strong>{{ $row->category->name }}</strong><span class="category-list-slug">{{ $row->category->slug }}</span><span class="category-list-parent">{{ $row->hierarchyLabel() }}</span></div></div></td>
                            <td data-mobile-label="Direct Products" data-category-count="products">{{ number_format($row->products) }}</td>
                            <td data-mobile-label="Attributes" data-category-count="attributes">{{ number_format($row->attributes) }}</td>
                            <td data-mobile-label="Facets" data-category-count="facets">{{ number_format($row->facets) }}</td>
                            <td data-mobile-label="Category status" class="category-list-status"><x-ui.status-badge :label="$row->category->status->label()" :tone="$row->statusTone()" size="sm" /></td>
                            <td data-mobile-label="Schema status" class="category-list-schema"><x-ui.status-badge :label="$row->category->schema_status->label()" :tone="$row->schemaTone()" size="sm" /></td>
                            <td data-mobile-label="Selected by Sites" data-category-count="sites">{{ number_format($row->sites) }}</td>
                            <td data-mobile-label="Translations" class="category-list-translations">
                                @if ($row->localeStatus)
                                    <strong>{{ $row->localeStatusLabel() }}</strong><span>{{ $list->localeOptions[$filters->localeId] }}</span>
                                @elseif ($row->coveragePercentage() === null)
                                    <span>No active Locales</span>
                                @else
                                    <strong>{{ $row->covered }}/{{ $row->localeTotal }} · {{ $row->coveragePercentage() }}%</strong>
                                    <span>{{ $row->missing }} missing · {{ $row->outdated }} outdated</span>
                                @endif
                            </td>
                            <td data-mobile-label="Updated" class="category-list-updated"><time datetime="{{ $row->category->updated_at?->toAtomString() }}">{{ $row->category->updated_at?->format('d M Y') }}</time></td>
                            <td class="category-list-actions-cell">@if ($actions[$row->category->id]['links'])<x-admin.row-actions :row-id="$row->category->id" display="menu" :actions="$actions[$row->category->id]['links']" />@else<span class="text-admin-muted">Read only</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="category-list-empty">
                            @if ($filters->hasConstraints())
                                <x-ui.states.filtered-empty id="categories-filtered-empty" title="No matching categories" message="No Categories match the current search and filters." :clear-url="$clearFiltersUrl" />
                            @else
                                <x-ui.states.empty id="categories-empty" title="No categories yet" message="There are no canonical Categories in the catalog." :action-label="$createUrl ? 'New Category' : null" :action-url="$createUrl" />
                            @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('central-admin.categories._pagination', ['categories' => $list->categories])
    </section>
    @foreach ($list->categories as $row)
        @foreach ($actions[$row->category->id]['commands'] as $command)
            <form id="{{ $command }}-category-{{ $row->category->id }}-form" method="POST" action="{{ route('central.categories.'.$command, [...$queryParameters, 'category' => $row->category]) }}" class="hidden">
                @csrf
                @if ($command === 'archive')<input type="hidden" name="confirmed" value="1">
                @endif
            </form>
            <x-admin.confirmation-modal id="{{ $command }}-category-{{ $row->category->id }}-modal" :title="ucfirst($command).' '.$row->category->name.'?'" :message="match ($command) { 'archive' => 'Existing Products, schema assignments, children, and Site selections are retained.', 'restore' => 'The Category returns to Draft. Activation is a separate action.', default => 'The Category becomes Active for canonical catalog use.' }" :confirm-label="ucfirst($command).' Category'" confirm-form="{{ $command }}-category-{{ $row->category->id }}-form" :variant="$command === 'archive' ? 'danger' : 'default'" :open="false" />
        @endforeach
    @endforeach
</div>
@endsection
