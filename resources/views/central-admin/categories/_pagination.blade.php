            <nav class="category-list-pagination" aria-label="Categories pagination">
                <p>Showing {{ number_format($categories->firstItem() ?? 0) }} to {{ number_format($categories->lastItem() ?? 0) }} of {{ number_format($categories->total()) }} categories</p>
                <form method="GET" action="{{ route('central.categories.index') }}" class="category-list-per-page">
                    @include('central-admin.categories._filter-inputs', ['excludedFilters' => ['per_page']])
                    <x-ui.form.select id="categories-per-page" name="per_page" label="Categories per page" :options="[20 => '20 per page', 50 => '50 per page', 100 => '100 per page']" :selected="$categories->perPage()" data-category-list-submit />
                </form>
                <div class="category-list-pages">
                    <a href="{{ $categories->url(1) }}" @class(['is-disabled' => $categories->onFirstPage()]) aria-label="First page" @if ($categories->onFirstPage()) aria-disabled="true" tabindex="-1" @endif><x-ui.icon name="chevron-double-left" /></a>
                    <a href="{{ $categories->previousPageUrl() ?? $categories->url(1) }}" @class(['is-disabled' => $categories->onFirstPage()]) aria-label="Previous page" @if ($categories->onFirstPage()) aria-disabled="true" tabindex="-1" @endif><x-ui.icon name="chevron-left" /></a>
                    @foreach ($categories->getUrlRange(max(1, $categories->currentPage() - 1), min($categories->lastPage(), $categories->currentPage() + 2)) as $page => $url)
                        <a href="{{ $url }}" @class(['is-active' => $page === $categories->currentPage()]) @if ($page === $categories->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                    @endforeach
                    @if ($categories->lastPage() > $categories->currentPage() + 2)
                        <span aria-hidden="true">…</span><a href="{{ $categories->url($categories->lastPage()) }}">{{ $categories->lastPage() }}</a>
                    @endif
                    <a href="{{ $categories->nextPageUrl() ?? $categories->url($categories->lastPage()) }}" @class(['is-disabled' => ! $categories->hasMorePages()]) aria-label="Next page" @if (! $categories->hasMorePages()) aria-disabled="true" tabindex="-1" @endif><x-ui.icon name="chevron-right" /></a>
                    <a href="{{ $categories->url($categories->lastPage()) }}" @class(['is-disabled' => ! $categories->hasMorePages()]) aria-label="Last page" @if (! $categories->hasMorePages()) aria-disabled="true" tabindex="-1" @endif><x-ui.icon name="chevron-double-right" /></a>
                </div>
            </nav>
