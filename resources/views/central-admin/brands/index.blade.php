@extends('layouts.central-admin', ['activeNav' => 'brands', 'pageTitle' => 'Brands'])

@php
    $brandRows = $brands->map(static function (\App\Models\CentralCatalog\CentralBrand $brand): array {
        $websiteHost = $brand->website_url === null ? null : parse_url($brand->website_url, PHP_URL_HOST);
        $websiteLabel = is_string($websiteHost)
            ? preg_replace('/\Awww\./i', '', $websiteHost) ?? $websiteHost
            : null;

        return [
            'id' => $brand->getKey(),
            'name' => $brand->name,
            'slug' => $brand->slug,
            'status' => ['label' => $brand->status->label(), 'variant' => $brand->status->color() === 'gray' ? 'neutral' : $brand->status->color()],
            'country' => $brand->country_code ?? '—',
            'website' => $brand->website_url === null ? '—' : ['label' => $websiteLabel, 'url' => $brand->website_url],
            'updated' => $brand->updated_at?->format('M j, Y') ?? '—',
        ];
    })->all();
    $brandListUrl = route('central.brands.index', absolute: false);
    $queryWithout = static fn (array $keys): array => request()->except($keys);
    $sortUrl = static function (string $key) use ($brandListUrl, $filters, $queryWithout): string {
        $direction = $filters->sort === $key && $filters->direction === 'asc' ? 'desc' : 'asc';
        $query = [...$queryWithout(['sort', 'direction', 'page']), 'sort' => $key, 'direction' => $direction];

        return $brandListUrl.'?'.http_build_query($query);
    };
    $columns = [
        ['key' => 'name', 'label' => 'Name', 'sortUrl' => $sortUrl('name'), 'sortDirection' => $filters->sort === 'name' ? $filters->direction : null],
        ['key' => 'slug', 'label' => 'Slug', 'sortUrl' => $sortUrl('slug'), 'sortDirection' => $filters->sort === 'slug' ? $filters->direction : null, 'responsive' => 'sm'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'status', 'sortUrl' => $sortUrl('status'), 'sortDirection' => $filters->sort === 'status' ? $filters->direction : null],
        ['key' => 'country', 'label' => 'Country', 'responsive' => 'md'],
        ['key' => 'website', 'label' => 'Website', 'type' => 'link', 'responsive' => 'lg'],
        ['key' => 'updated', 'label' => 'Updated', 'sortUrl' => $sortUrl('updated_at'), 'sortDirection' => $filters->sort === 'updated_at' ? $filters->direction : null, 'responsive' => 'md'],
    ];
    $activeFilters = [];
    if ($filters->status !== null) {
        $activeFilters[] = [
            'key' => 'status',
            'label' => 'Status: '.$statusOptions[$filters->status],
            'removeUrl' => $brandListUrl.'?'.http_build_query($queryWithout(['status', 'page'])),
        ];
    }
@endphp

@section('content')
    <div class="space-y-admin-section" data-brand-list-fixture="brands-list-v1">
        <x-admin.page-header
            screen-id="CA-011"
            title="Brands"
            description="Canonical brands used across the central catalog."
            :breadcrumbs="[['label' => 'Central Admin', 'url' => route('filament.central.pages.home', absolute: false)], ['label' => 'Brands']]"
        />

        <x-admin.card>
            <div class="space-y-admin-card">
                <x-admin.table-toolbar :action="$brandListUrl" search-id="brand-search" search-label="Search brands" :search="$filters->search">
                    @if ($filters->status !== null)<input type="hidden" name="status" value="{{ $filters->status }}">@endif
                    <input type="hidden" name="sort" value="{{ $filters->sort }}">
                    <input type="hidden" name="direction" value="{{ $filters->direction }}">
                    <input type="hidden" name="per_page" value="{{ $filters->perPage }}">
                </x-admin.table-toolbar>

                <x-admin.filter-bar :action="$brandListUrl" drawer-id="brand-filters">
                    @if ($filters->search !== null)<input type="hidden" name="q" value="{{ $filters->search }}">@endif
                    <input type="hidden" name="sort" value="{{ $filters->sort }}">
                    <input type="hidden" name="direction" value="{{ $filters->direction }}">
                    <input type="hidden" name="per_page" value="{{ $filters->perPage }}">
                    <x-ui.form.select id="brand-status" name="status" label="Status" :options="$statusOptions" :selected="$filters->status" placeholder="All statuses" />
                </x-admin.filter-bar>

                <x-admin.active-filters :filters="$activeFilters" :clear-all-url="$brandListUrl" />

                @if ($brands->isEmpty())
                    @if ($filters->hasConstraints())
                        <x-ui.states.filtered-empty
                            id="brands-filtered-empty"
                            title="No matching brands"
                            message="No brands match your current search or filters."
                            :clear-url="$brandListUrl"
                        />
                    @else
                        <x-ui.states.empty
                            id="brands-empty"
                            title="No brands yet"
                            message="Canonical brands will appear here once they are created."
                        />
                    @endif
                @else
                    <x-admin.data-table
                        table-id="brands-table"
                        caption="Canonical brands"
                        :columns="$columns"
                        :rows="$brandRows"
                        mobile-compact
                        data-screen-region="brands-table"
                    />

                    <div class="flex flex-col gap-admin-field sm:flex-row sm:items-end sm:justify-between">
                        <form method="GET" action="{{ $brandListUrl }}" class="w-full sm:w-36">
                            @foreach ($queryWithout(['per_page', 'page']) as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                            <x-ui.form.select id="brands-per-page" name="per_page" label="Per page" :options="[20 => '20', 50 => '50', 100 => '100']" :selected="$filters->perPage" />
                            <button type="submit" class="sr-only">Apply page size</button>
                        </form>

                        <x-admin.pagination
                            :previous-url="$brands->previousPageUrl()"
                            :next-url="$brands->nextPageUrl()"
                            :page="$brands->currentPage()"
                        />
                    </div>
                @endif
            </div>
        </x-admin.card>
    </div>
@endsection
