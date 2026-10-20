@if ($impact)
<section class="category-slug-impact" aria-labelledby="category-slug-impact-heading" data-category-slug-impact data-impact-fingerprint="{{ $impact->fingerprint }}" data-requires-acknowledgement="{{ $impact->requiresAcknowledgement() ? 'true' : 'false' }}">
    <h3 id="category-slug-impact-heading">Routing impact of this slug change</h3>
    <p class="category-form-note">A consequence assessment of the current Site state, not a publication or a guarantee that a new public route is live.</p>
    <dl class="category-slug-impact-facts">
        <div><dt>Current slug</dt><dd><code data-impact-current-slug>{{ $impact->currentSlug }}</code></dd></div>
        <div><dt>Proposed slug</dt><dd><code data-impact-proposed-slug>{{ $impact->proposedSlug }}</code></dd></div>
        <div><dt>Category ID</dt><dd><code>{{ $impact->categoryId }}</code> — unchanged</dd></div>
        <div><dt>Selected by Sites</dt><dd data-impact-selected-sites>{{ number_format($impact->selectedSiteCount) }}@if ($impact->selectedSites) <span>({{ collect($impact->selectedSites)->map(fn (array $site): string => $site['name'].' · '.$site['status'])->implode(', ') }}@if ($impact->selectedSiteCount > count($impact->selectedSites)), +{{ $impact->selectedSiteCount - count($impact->selectedSites) }} more @endif)</span>@endif</dd></div>
        <div><dt>Persisted Site projections</dt><dd data-impact-projections>{{ number_format($impact->projectionCount) }} · {{ number_format($impact->currentSlugProjectionCount) }} use the current slug · {{ number_format($impact->servedRouteCount) }} served now</dd></div>
    </dl>

    @if ($impact->routes)
        <ul class="category-slug-impact-routes" aria-label="Affected Site routes">
            @foreach ($impact->routes as $route)
                <li data-impact-route>
                    <p class="category-slug-impact-route-title"><strong>{{ $route->siteName }}</strong> · {{ $route->locale }} · Site {{ $route->siteStatus }} · Projection {{ $route->projectionStatus }}</p>
                    <p><span>Now:</span>
                        @if ($route->currentUrl)<a href="{{ $route->currentUrl }}" rel="noopener" target="_blank"><code>{{ $route->currentUrl }}</code></a>
                        @else <code>{{ $route->currentPath }}</code>@endif
                        — {{ $route->servedNow ? 'served' : 'not served: '.$route->notServedReason }}
                    </p>
                    <p><span>After the next Site sync:</span>
                        @if ($route->keptByOverride && ! $route->changesOnRebuild())
                            unchanged — a Site or market slug override keeps <code>{{ $route->slugAfterRebuild }}</code>
                        @elseif ($route->urlAfterRebuild)
                            <code>{{ $route->urlAfterRebuild }}</code>
                        @else
                            <code>{{ $route->pathAfterRebuild }}</code>
                        @endif
                    </p>
                </li>
            @endforeach
        </ul>
        @if ($impact->hiddenRouteCount > 0)<p class="category-form-note">{{ number_format($impact->hiddenRouteCount) }} more persisted projections are included in the totals above.</p>@endif
    @endif

    <ul class="category-slug-impact-notes">
        <li>Public Category pages resolve persisted Site projections, not the canonical slug. Saving does not change a live route by itself.</li>
        <li>Existing routes keep serving their current slug until the owning Site sync rebuilds the Category projection; after that rebuild the previous path no longer resolves.</li>
        <li>No automatic HTTP redirect is created. Category links in Product and neighbouring Category pages update only when those projections are rebuilt.</li>
        <li>Site and market slug overrides take precedence and are not changed. Site selection and publication follow their own workflows.</li>
    </ul>

    <input type="hidden" name="slug_impact_fingerprint" value="{{ $impact->fingerprint }}">
    @if ($impact->requiresAcknowledgement())
        <x-ui.form.checkbox
            id="category-slug-impact-acknowledged"
            name="slug_impact_acknowledged"
            label="I reviewed this routing impact and understand that no redirect is created."
            :error="$errors->first('slug_impact_acknowledged')"
            data-slug-impact-acknowledgement
        />
    @else
        <p class="category-form-note" data-slug-impact-no-acknowledgement>No Site selects this Category and no Site projection exists for it, so no confirmation is required.</p>
    @endif
</section>
@endif
