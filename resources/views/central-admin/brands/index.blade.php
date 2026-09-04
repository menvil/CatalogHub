@extends('layouts.central-admin', ['activeNav' => 'brands', 'pageTitle' => 'Brands'])

@section('breadcrumbs')
    <a href="{{ route('filament.central.pages.home', absolute: false) }}" class="font-medium hover:text-admin-text">Dashboard</a>
    <span aria-hidden="true">/</span>
    <span aria-current="page">Brands</span>
@endsection

@section('content')
    @php
        $brands = $list->brands;
        $summary = $list->summary;
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
        $percent = static fn (int $value): string => number_format($summary->percentage($value) ?? 0, 1).'% of total';
        $activeFilterCount = collect([
            $filters->search,
            $filters->countryId,
            $filters->status,
            $filters->categoryCoverage,
            $filters->translation,
            $filters->quality,
        ])->filter(fn ($value) => $value !== null)->count();
        $clearFilterQuery = [];
        if (request()->filled('sort')) {
            $clearFilterQuery['sort'] = request('sort');
            $clearFilterQuery['direction'] = request('direction', 'asc');
        }
        if (request()->filled('per_page')) {
            $clearFilterQuery['per_page'] = request('per_page');
        }
        $clearFiltersUrl = route('central.brands.index', $clearFilterQuery, absolute: false);
    @endphp

    <div class="brand-list-page" data-screen-id="CA-011" data-fixture-version="brands-list-v3">
        <header class="brand-list-heading">
            <div class="min-w-0">
                <h1 class="text-foundation-heading font-semibold text-admin-text">Brands</h1>
                <p class="max-w-3xl text-sm text-admin-muted">Manage brand profiles, product associations, media assets, and localization across your catalog.</p>
            </div>
            @can('catalog.brands.manage')
                <x-ui.button :href="route('central.brands.create', absolute: false)">New Brand</x-ui.button>
            @endcan
        </header>

        <section class="brand-list-metrics" aria-label="Brand summary">
            @foreach ([
                ['label' => 'Total Brands', 'value' => $summary->total, 'icon' => 'tag', 'tone' => 'primary', 'detail' => null],
                ['label' => 'Active', 'value' => $summary->active, 'icon' => 'check-circle', 'tone' => 'success', 'detail' => $summary->total > 0 ? $percent($summary->active) : null],
                ['label' => 'With Logos', 'value' => $summary->withLogos, 'icon' => 'photo', 'tone' => 'info', 'detail' => $summary->total > 0 ? $percent($summary->withLogos) : null],
                ['label' => 'Missing Translations', 'value' => $summary->missingTranslations, 'icon' => 'language', 'tone' => 'danger', 'detail' => $summary->total > 0 ? $percent($summary->missingTranslations) : null],
                ['label' => 'Needs attention', 'value' => $summary->needsAttention, 'icon' => 'exclamation-triangle', 'tone' => 'warning', 'detail' => $summary->total > 0 ? $percent($summary->needsAttention) : null],
            ] as $metric)
                <article class="brand-list-metric brand-list-metric--{{ $metric['tone'] }}" data-brand-metric="{{ str($metric['label'])->slug() }}">
                    <div class="brand-list-metric-label">
                        <span class="brand-list-metric-icon"><x-ui.icon :name="$metric['icon']" size="sm" /></span>
                        <span>{{ $metric['label'] }}</span>
                    </div>
                    <strong>{{ number_format($metric['value']) }}</strong>
                    <span class="brand-list-metric-detail" @if (! $metric['detail']) aria-hidden="true" @endif>{{ $metric['detail'] ?: 'Current catalog' }}</span>
                </article>
            @endforeach
        </section>

        <section class="brand-list-surface" aria-label="Brands list">
            <form method="GET" action="{{ route('central.brands.index') }}" class="brand-list-filters">
                <div class="brand-list-search">
                    <label for="brand-search">Search</label>
                    <div class="brand-list-search-control">
                        <x-ui.icon name="magnifying-glass" />
                        <input id="brand-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search brands by name, slug, or company…" data-brand-list-search>
                    </div>
                </div>

                <div class="brand-list-filter brand-list-filter--country">
                    <x-ui.form.searchable-select id="brand-country" name="country" label="Country" placeholder="All countries" search-placeholder="Search countries…" :options="$countryOptions" :selected="request('country')" data-brand-list-submit />
                </div>
                <div class="brand-list-filter">
                    <x-ui.form.select id="brand-status" name="status" label="Status" placeholder="All" :options="$statusOptions" :selected="request('status')" data-brand-list-submit />
                </div>
                <div class="brand-list-filter">
                    <x-ui.form.select id="brand-coverage" name="coverage" label="Category Coverage" placeholder="All" :options="['has' => 'Has coverage', 'none' => 'No coverage']" :selected="request('coverage')" data-brand-list-submit />
                </div>
                <div class="brand-list-filter">
                    <x-ui.form.select id="brand-translation" name="translation" label="Translation" placeholder="All" :options="['complete' => 'Complete', 'missing' => 'Missing', 'outdated' => 'Outdated', 'needs_attention' => 'Needs attention']" :selected="request('translation')" data-brand-list-submit />
                </div>
                <div class="brand-list-filter">
                    <x-ui.form.select id="brand-quality" name="quality" label="Quality" placeholder="All" :options="['complete' => 'Complete', 'needs_attention' => 'Needs attention']" :selected="request('quality')" data-brand-list-submit />
                </div>
                @include('central-admin.brands._filter-inputs', ['excludedFilters' => ['q', 'country', 'status', 'coverage', 'translation', 'quality']])
                @if (request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif
            </form>

            @if ($filters->hasConstraints())
                <div class="brand-list-active-filters" data-brand-active-filter-count="{{ $activeFilterCount }}">
                    <span>{{ $activeFilterCount }} active {{ str('filter')->plural($activeFilterCount) }}</span>
                    <a href="{{ $clearFiltersUrl }}">Clear filters</a>
                </div>
            @endif

            @if ($errors->any())
                <p class="brand-list-error" role="alert">{{ $errors->first() }}</p>
            @endif

            <div class="brand-list-table-wrap" data-admin-data-table>
                <table class="brand-list-table w-full border-collapse text-sm">
                    <caption class="sr-only">Brands</caption>
                    <thead class="bg-admin-surface-muted text-admin-muted">
                        <tr>
                            <th scope="col" aria-sort="{{ $ariaSort('name') }}"><a href="{{ $sortUrl('name') }}" class="brand-list-sort">Brand <span aria-hidden="true">{{ $currentSort === 'name' ? ($currentDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th>
                            <th scope="col" class="brand-list-col-secondary">Category Coverage</th>
                            <th scope="col" class="brand-list-col-secondary" aria-sort="{{ $ariaSort('products') }}"><a href="{{ $sortUrl('products') }}" class="brand-list-sort">Products <span aria-hidden="true">{{ $currentSort === 'products' ? ($currentDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th>
                            <th scope="col" aria-sort="{{ $ariaSort('status') }}"><a href="{{ $sortUrl('status') }}" class="brand-list-sort">Status <span aria-hidden="true">{{ $currentSort === 'status' ? ($currentDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th>
                            <th scope="col">Translation Coverage</th>
                            <th scope="col">Quality</th>
                            <th scope="col" class="brand-list-col-secondary" aria-sort="{{ $ariaSort('updated_at') }}"><a href="{{ $sortUrl('updated_at') }}" class="brand-list-sort">Updated <span aria-hidden="true">{{ $currentSort === 'updated_at' ? ($currentDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></a></th>
                            <th scope="col" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-admin-border">
                        @forelse ($brands as $row)
                            @php
                                $brand = $row->brand;
                                $status = $brand->status;
                                $statusTone = match ($status) {
                                    \App\Enums\CentralBrandStatus::Active => 'success',
                                    \App\Enums\CentralBrandStatus::Archived => 'danger',
                                    default => 'neutral',
                                };
                                $translationScore = $row->health->translations->total > 0 ? $row->health->translations->score() : null;
                                $quality = $row->health->summary;
                                $logoState = $row->health->logo->state;
                                $translationBreakdown = array_values(array_filter([
                                    $row->health->translations->missing > 0 ? number_format($row->health->translations->missing).' missing' : null,
                                    $row->health->translations->outdated > 0 ? number_format($row->health->translations->outdated).' outdated' : null,
                                ]));
                            @endphp
                            <tr data-row-id="{{ $brand->getKey() }}">
                                <td class="brand-list-brand-cell">
                                    <div class="brand-list-identity">
                                        <span class="brand-list-logo-shell">
                                            <span @class(['brand-list-logo', 'has-logo' => $row->health->logo->url !== null]) data-logo-state="{{ $logoState->value }}">
                                                @if ($row->health->logo->url)
                                                    <img src="{{ $row->health->logo->url }}" alt="" loading="lazy">
                                                @else
                                                    <span aria-hidden="true">{{ mb_strtoupper(mb_substr($brand->name, 0, 1)) }}</span>
                                                @endif
                                            </span>
                                            @if (! in_array($logoState, [\App\Enums\MediaDeliveryState::Ready, \App\Enums\MediaDeliveryState::Missing], true))
                                                <span class="brand-list-logo-warning" title="Logo unavailable"><x-ui.icon name="exclamation-triangle" decorative size="sm" /><span class="sr-only">Logo unavailable</span></span>
                                            @endif
                                        </span>
                                        <span class="brand-list-identity-copy">
                                            <strong>{{ $brand->name }}</strong>
                                            <span class="brand-list-slug">{{ $brand->slug }}</span>
                                            @if ($brand->ownership?->organization)
                                                <span class="brand-list-company">{{ $brand->ownership->organization->name }}</span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td class="brand-list-category-cell" data-mobile-label="Categories"><span class="brand-list-count">{{ $row->categoryCount > 0 ? trans_choice(':count category|:count categories', $row->categoryCount, ['count' => number_format($row->categoryCount)]) : 'No coverage' }}</span></td>
                                <td class="brand-list-products-cell" data-mobile-label="Products"><strong class="brand-list-numeric">{{ number_format((int) $brand->products_count) }}</strong></td>
                                <td class="brand-list-status-cell"><x-ui.status-badge :label="$status->label()" :tone="$statusTone" size="sm" /></td>
                                <td class="brand-list-translation-cell">
                                    @if ($translationScore === null)
                                        <span class="brand-list-neutral">—</span>
                                    @else
                                        <div class="brand-list-coverage">
                                            <strong>{{ $translationScore }}%</strong>
                                            <span class="brand-list-progress" role="progressbar" aria-label="{{ $brand->name }} translation coverage" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $translationScore }}"><span style="width: {{ $translationScore }}%"></span></span>
                                        </div>
                                    @endif
                                    @if ($translationBreakdown !== [])
                                        <span class="brand-list-translation-breakdown" data-brand-translation-breakdown>{{ implode(' · ', $translationBreakdown) }}</span>
                                    @endif
                                </td>
                                <td class="brand-list-quality-cell">
                                    <div class="brand-list-quality">
                                        <strong>{{ $quality->score }}%</strong>
                                        <x-ui.status-badge :label="$quality->state->label()" :tone="$quality->state->badgeVariant()" size="sm" data-brand-quality="{{ $quality->state->value }}" />
                                    </div>
                                </td>
                                <td class="brand-list-updated-cell brand-list-muted"><time datetime="{{ $brand->updated_at?->toAtomString() }}">{{ $brand->updated_at?->diffForHumans() }}</time></td>
                                <td class="brand-list-actions-cell">
                                    <x-admin.row-actions :row-id="$brand->getKey()" display="menu" :actions="[
                                        ['label' => 'View', 'url' => route('central.brands.show', $brand, absolute: false)],
                                        ['label' => 'Edit', 'url' => route('central.brands.edit', $brand, absolute: false)],
                                    ]" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="brand-list-empty p-4 text-admin-muted">
                                    @if ($filters->hasConstraints())
                                        <x-ui.states.filtered-empty id="brands-filtered-empty" title="No matching brands" message="No brands match the current search and filters." :clear-url="$clearFiltersUrl" />
                                    @else
                                        <x-ui.states.empty id="brands-empty" title="No brands yet" message="Create the first canonical brand in the central catalog." action-label="New Brand" :action-url="route('central.brands.create', absolute: false)" />
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <nav class="brand-list-pagination" aria-label="Brands pagination">
                <p>Showing {{ number_format($brands->firstItem() ?? 0) }} to {{ number_format($brands->lastItem() ?? 0) }} of {{ number_format($brands->total()) }} brands</p>
                <form method="GET" action="{{ route('central.brands.index') }}" class="brand-list-per-page">
                    @include('central-admin.brands._filter-inputs', ['excludedFilters' => []])
                    <x-ui.form.select id="brands-per-page" name="per_page" label="Brands per page" :options="[20 => '20 per page', 50 => '50 per page', 100 => '100 per page']" :selected="$brands->perPage()" data-brand-list-submit />
                </form>
                <div class="brand-list-pages">
                    <a href="{{ $brands->url(1) }}" @class(['is-disabled' => $brands->onFirstPage()]) aria-label="First page" @if ($brands->onFirstPage()) aria-disabled="true" tabindex="-1" @endif><x-ui.icon name="chevron-double-left" /></a>
                    <a href="{{ $brands->previousPageUrl() ?? $brands->url(1) }}" @class(['is-disabled' => $brands->onFirstPage()]) aria-label="Previous page" @if ($brands->onFirstPage()) aria-disabled="true" tabindex="-1" @endif><x-ui.icon name="chevron-left" /></a>
                    @foreach ($brands->getUrlRange(max(1, $brands->currentPage() - 1), min($brands->lastPage(), $brands->currentPage() + 2)) as $page => $url)
                        <a href="{{ $url }}" @class(['is-active' => $page === $brands->currentPage()]) @if ($page === $brands->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                    @endforeach
                    @if ($brands->lastPage() > $brands->currentPage() + 2)
                        <span aria-hidden="true">…</span><a href="{{ $brands->url($brands->lastPage()) }}">{{ $brands->lastPage() }}</a>
                    @endif
                    <a href="{{ $brands->nextPageUrl() ?? $brands->url($brands->lastPage()) }}" @class(['is-disabled' => ! $brands->hasMorePages()]) aria-label="Next page" @if (! $brands->hasMorePages()) aria-disabled="true" tabindex="-1" @endif><x-ui.icon name="chevron-right" /></a>
                    <a href="{{ $brands->url($brands->lastPage()) }}" @class(['is-disabled' => ! $brands->hasMorePages()]) aria-label="Last page" @if (! $brands->hasMorePages()) aria-disabled="true" tabindex="-1" @endif><x-ui.icon name="chevron-double-right" /></a>
                </div>
            </nav>
        </section>
    </div>
@endsection
