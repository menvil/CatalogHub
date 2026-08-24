@extends('layouts.central-admin', ['activeNav' => 'brands', 'pageTitle' => 'Brands'])

@section('breadcrumbs')
    <a href="{{ route('filament.central.pages.home', absolute: false) }}" class="font-medium hover:text-admin-text">Dashboard</a>
    <span aria-hidden="true">/</span>
    <span aria-current="page">Brands</span>
@endsection

@section('content')
    @php
        $currentSort = request('sort', 'name');
        $currentDirection = request('direction', 'asc');
        $sortQuery = request()->except(['sort', 'direction', 'page']);
        $sortUrl = static function (string $column) use ($currentSort, $currentDirection, $sortQuery): string {
            $direction = $currentSort === $column && $currentDirection === 'asc' ? 'desc' : 'asc';

            return route('central.brands.index', [...$sortQuery, 'sort' => $column, 'direction' => $direction]);
        };
        $ariaSort = static fn (string $column): string => $currentSort === $column
            ? ($currentDirection === 'desc' ? 'descending' : 'ascending')
            : 'none';
    @endphp

    <div class="brand-list-page" data-screen-id="CA-011">
        <header class="brand-list-heading">
            <div class="min-w-0">
                <h1 class="text-foundation-heading font-semibold text-admin-text">Brands</h1>
                <p class="max-w-3xl text-sm text-admin-muted">Manage brand profiles, product associations, media assets, and localization across your catalog.</p>
            </div>
            <x-ui.button :href="route('central.brands.create', absolute: false)">Add Brand</x-ui.button>
        </header>

        <section class="brand-list-surface" aria-label="Brands list">
            <form method="GET" action="{{ route('central.brands.index') }}" class="brand-list-filters">
                <div class="brand-list-search">
                    <label for="brand-search">Search</label>
                    <div class="brand-list-search-control">
                        <x-ui.icon name="magnifying-glass" />
                        <input
                            id="brand-search"
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Name or slug..."
                            data-brand-list-search
                        >
                    </div>
                </div>

                <div class="brand-list-status">
                    <x-ui.form.select
                        id="brand-status"
                        name="status"
                        label="Status"
                        placeholder="All"
                        :options="['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived']"
                        :selected="request('status')"
                        data-brand-list-submit
                    />
                </div>
                @include('central-admin.brands._filter-inputs', ['excludedFilters' => ['q', 'status']])
                @if (request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif
            </form>

            @if ($errors->any())
                <p class="brand-list-error" role="alert">{{ $errors->first() }}</p>
            @endif

            <div class="brand-list-table-wrap" data-admin-data-table>
            <table class="brand-list-table min-w-foundation-table w-full border-collapse text-sm">
                <caption class="sr-only">Brands</caption>
                <thead class="bg-admin-surface-muted text-admin-muted">
                    <tr>
                        <th scope="col" class="px-3 py-2 font-semibold" aria-sort="{{ $ariaSort('name') }}">
                            <a href="{{ $sortUrl('name') }}" class="brand-list-sort">
                                Brand
                                <span aria-hidden="true">{{ $currentSort === 'name' ? ($currentDirection === 'asc' ? '↑' : '↓') : '↕' }}</span>
                            </a>
                        </th>
                        <th scope="col" class="px-3 py-2 font-semibold" aria-sort="{{ $ariaSort('status') }}">
                            <a href="{{ $sortUrl('status') }}" class="brand-list-sort">
                                Status
                                <span aria-hidden="true">{{ $currentSort === 'status' ? ($currentDirection === 'asc' ? '↑' : '↓') : '↕' }}</span>
                            </a>
                        </th>
                        <th scope="col" class="px-3 py-2 font-semibold">Updated</th>
                        <th scope="col" class="px-3 py-2 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-admin-border">
                    @forelse ($brands as $brand)
                        @php
                            $status = $brand->status;
                            $tone = match ($status) {
                                \App\Enums\CentralBrandStatus::Active => 'success',
                                \App\Enums\CentralBrandStatus::Archived => 'danger',
                                default => 'neutral',
                            };
                        @endphp
                        <tr data-row-id="{{ $brand->getKey() }}">
                            <td class="px-3 py-2 text-admin-text">
                                <strong>{{ $brand->name }}</strong>
                                <span class="brand-list-slug">{{ $brand->slug }}</span>
                            </td>
                            <td class="px-3 py-2 text-admin-text"><x-ui.status-badge :label="$status->label()" :tone="$tone" size="sm" /></td>
                            <td class="brand-list-muted px-3 py-2 text-admin-text">{{ $brand->updated_at?->diffForHumans() }}</td>
                            <td class="px-3 py-2 text-admin-text">
                                <x-admin.row-actions
                                    :row-id="$brand->getKey()"
                                    :actions="[
                                        [
                                            'label' => 'View',
                                            'url' => route('central.brands.show', $brand, absolute: false),
                                        ],
                                        [
                                            'label' => 'Edit',
                                            'url' => route('central.brands.edit', $brand, absolute: false),
                                        ],
                                    ]"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="brand-list-empty p-4 text-admin-muted">
                                @if ($filters->hasConstraints())
                                    <x-ui.states.filtered-empty
                                        id="brands-filtered-empty"
                                        title="No matching brands"
                                        message="No brands match the current search and filters."
                                        :clear-url="route('central.brands.index', absolute: false)"
                                    />
                                @else
                                    <x-ui.states.empty
                                        id="brands-empty"
                                        title="No brands yet"
                                        message="Create the first canonical brand in the central catalog."
                                        action-label="Add Brand"
                                        :action-url="route('central.brands.create', absolute: false)"
                                    />
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>

            <nav class="brand-list-pagination" aria-label="Brands pagination">
            <p>
                Showing {{ number_format($brands->firstItem() ?? 0) }} to {{ number_format($brands->lastItem() ?? 0) }}
                of {{ number_format($brands->total()) }} brands.
            </p>

                <form method="GET" action="{{ route('central.brands.index') }}" class="brand-list-per-page">
                    @include('central-admin.brands._filter-inputs', ['excludedFilters' => []])
                    <x-ui.form.select
                        id="brands-per-page"
                        name="per_page"
                        label="Brands per page"
                        :options="[20 => '20 per page', 50 => '50 per page', 100 => '100 per page']"
                        :selected="$brands->perPage()"
                        data-brand-list-submit
                    />
                </form>

                <div class="brand-list-pages">
                <a href="{{ $brands->url(1) }}" @class(['is-disabled' => $brands->onFirstPage()]) aria-label="First page" @if ($brands->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>
                    <x-ui.icon name="chevron-double-left" />
                </a>
                <a href="{{ $brands->previousPageUrl() ?? $brands->url(1) }}" @class(['is-disabled' => $brands->onFirstPage()]) aria-label="Previous page" @if ($brands->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>
                    <x-ui.icon name="chevron-left" />
                </a>

                @foreach ($brands->getUrlRange(max(1, $brands->currentPage() - 1), min($brands->lastPage(), $brands->currentPage() + 2)) as $page => $url)
                    <a href="{{ $url }}" @class(['is-active' => $page === $brands->currentPage()]) @if ($page === $brands->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                @endforeach

                @if ($brands->lastPage() > $brands->currentPage() + 2)
                    <span aria-hidden="true">…</span>
                    <a href="{{ $brands->url($brands->lastPage()) }}">{{ $brands->lastPage() }}</a>
                @endif

                <a href="{{ $brands->nextPageUrl() ?? $brands->url($brands->lastPage()) }}" @class(['is-disabled' => ! $brands->hasMorePages()]) aria-label="Next page" @if (! $brands->hasMorePages()) aria-disabled="true" tabindex="-1" @endif>
                    <x-ui.icon name="chevron-right" />
                </a>
                <a href="{{ $brands->url($brands->lastPage()) }}" @class(['is-disabled' => ! $brands->hasMorePages()]) aria-label="Last page" @if (! $brands->hasMorePages()) aria-disabled="true" tabindex="-1" @endif>
                    <x-ui.icon name="chevron-double-right" />
                </a>
                </div>
            </nav>
        </section>
    </div>
@endsection
