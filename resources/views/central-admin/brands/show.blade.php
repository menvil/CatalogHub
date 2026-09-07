@extends('layouts.central-admin', ['activeNav' => 'brands', 'pageTitle' => $brand->name])

@php
    $statusVariant = $brand->status->color() === 'gray' ? 'neutral' : $brand->status->color();
    $websiteIsSafe = \App\Support\Presentation\SafePresentationUrl::allows($brand->website_url);
    $supportUrlIsSafe = \App\Support\Presentation\SafePresentationUrl::allows($brand->support_url);
    $websiteLabel = $websiteIsSafe ? (parse_url((string) $brand->website_url, PHP_URL_HOST) ?: $brand->website_url) : $brand->website_url;
    $supportUrlLabel = $supportUrlIsSafe ? 'Open support site' : $brand->support_url;
    $primaryColorIsSafe = is_string($brand->primary_color)
        && preg_match('/\A#[0-9A-F]{6}\z/', $brand->primary_color) === 1;
    $productsCount = (int) $brand->products_count;
    $parentCompany = $brand->ownership?->organization;
    $lifecycleError = $errors->first('status') ?: session('lifecycle_error');
    $tagError = $errors->first('tags');
    if ($tagError === '') {
        $tagError = collect($errors->getBag('default')->getMessages())
            ->filter(static fn (array $messages, string $key): bool => str_starts_with($key, 'tags.'))
            ->flatten()
            ->first() ?? '';
    }
    $oldTagEditorValues = old('tags');
    if ($tagError === '' && $oldTagEditorValues !== null) {
        $tagError = 'Review the submitted tags. Tags must be nonblank, at most 80 characters, and contain no control characters or newlines.';
    }
    $persistedTagEditorValues = $brand->tags->pluck('name')->all();
    $tagEditorValues = $oldTagEditorValues ?? $persistedTagEditorValues;
    $tagEditorValues = is_array($tagEditorValues) ? array_values($tagEditorValues) : [];
    $tagEditorOpen = $tagError !== '' || $oldTagEditorValues !== null;
    $externalIdentityModal = session('external_identity_modal');
    $externalIdentityErrors = session('external_identity_errors', []);
    $externalIdentityErrors = is_array($externalIdentityErrors) ? $externalIdentityErrors : [];
    $externalIdentityError = static fn (string $field): ?string => isset($externalIdentityErrors[$field][0])
        && is_string($externalIdentityErrors[$field][0])
            ? $externalIdentityErrors[$field][0]
            : null;
    $externalIdentityAddOpen = $externalIdentityModal === 'add';
    $externalIdentityEditId = is_string($externalIdentityModal) && ctype_digit($externalIdentityModal)
        ? (int) $externalIdentityModal
        : null;
    $activeSourceOptions = $activeImportSources->mapWithKeys(
        static fn ($source): array => [$source->getKey() => $source->name.' ('.$source->code.')'],
    )->all();
    $qualityIssues = collect($quality->issues());
    $qualityIssueDomain = static fn ($issue): string => match ($issue->issueCode) {
        \App\Enums\CentralBrandQualityIssueCode::LogoMissing,
        \App\Enums\CentralBrandQualityIssueCode::LogoUnusable => 'Media',
        \App\Enums\CentralBrandQualityIssueCode::TranslationMissing,
        \App\Enums\CentralBrandQualityIssueCode::TranslationOutdated => 'Translation',
        default => 'Profile',
    };
    $representativeQualityIssues = collect(['Profile', 'Media', 'Translation'])
        ->map(static fn (string $domain) => $qualityIssues->first(
            static fn ($issue): bool => $qualityIssueDomain($issue) === $domain,
        ))
        ->filter();
    $visibleQualityIssues = $representativeQualityIssues
        ->concat($qualityIssues)
        ->unique(static fn ($issue): string => $issue->key)
        ->take(4)
        ->values();
    $remainingQualityIssues = max(0, $qualityIssues->count() - $visibleQualityIssues->count());
@endphp

@section('breadcrumbs')
    <a href="{{ route('filament.central.pages.home', absolute: false) }}" class="font-medium hover:text-admin-text">Central Admin</a>
    <span aria-hidden="true">/</span>
    <a href="{{ route('central.brands.index', absolute: false) }}" class="font-medium hover:text-admin-text">Brands</a>
    <span aria-hidden="true">/</span>
    <span aria-current="page">{{ $brand->name }}</span>
@endsection

@section('content')
    <div class="brand-detail-page" data-brand-detail-fixture="brand-detail-v8">
        <header class="brand-detail-heading" data-screen-id="CA-012">
            <div class="min-w-0">
                <div class="flex min-w-0 flex-wrap items-center gap-3">
                    <h1 class="break-words text-foundation-heading font-semibold text-admin-text">{{ $brand->name }}</h1>
                    <div data-screen-region="status-context">
                        <x-admin.status-badge :label="$brand->status->label()" :variant="$statusVariant" />
                    </div>
                    <x-admin.status-badge :label="$quality->state->label()" :variant="$quality->state->badgeVariant()" />
                </div>
                <p class="mt-2 flex min-w-0 items-center gap-2 text-sm text-admin-muted">
                    <span class="break-all font-foundation-mono">{{ $brand->slug }}</span>
                    <span aria-hidden="true">·</span>
                    <span>Canonical brand in the central catalog.</span>
                </p>
            </div>
            @can('catalog.brands.manage')
                <div class="brand-detail-heading-actions" data-page-actions>
                    <x-ui.button :href="route('central.brands.edit', $brand, absolute: false)">Edit Brand</x-ui.button>
                </div>
            @endcan
        </header>

        @include('central-admin.brands.partials.subnav', ['active' => 'overview'])

        <div class="brand-detail-layout" data-admin-detail-layout>
            <div class="brand-detail-main" data-brand-detail-main>
                <x-admin.card class="brand-detail-profile min-w-0" data-screen-region="brand-identity">
                    <div class="flex min-w-0 flex-wrap items-start justify-between gap-3 border-b border-admin-border pb-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-admin-primary">Brand profile</p>
                            <h2 class="mt-1 text-lg font-semibold text-admin-text">Identity and contact</h2>
                        </div>
                        @can('catalog.brands.manage')
                            <a href="{{ route('central.brands.media', $brand, absolute: false) }}" class="inline-flex text-sm font-semibold text-admin-primary underline decoration-admin-primary/30 underline-offset-2">Manage logo</a>
                        @endcan
                    </div>

                    <div class="brand-detail-profile-grid mt-admin-card">
                        <div class="brand-detail-logo" data-logo-delivery-state="{{ $logo->state->value }}">
                            @if ($logo->state === \App\Enums\MediaDeliveryState::Ready && $logo->url !== null)
                                <img src="{{ $logo->url }}" alt="{{ $brand->name }} logo" class="h-full w-full object-contain">
                            @elseif ($logo->state === \App\Enums\MediaDeliveryState::Missing || $logo->asset === null)
                                <div class="text-center">
                                    <p class="text-sm font-semibold text-admin-text">No logo</p>
                                    <p class="mt-1 text-xs text-admin-muted">Global primary logo is not assigned.</p>
                                </div>
                            @else
                                @php
                                    $logoStateCopy = match ($logo->state) {
                                        \App\Enums\MediaDeliveryState::Processing => 'The assigned logo is still processing.',
                                        \App\Enums\MediaDeliveryState::Failed => 'Processing failed for the assigned logo.',
                                        default => 'A logo is assigned, but no usable file is currently available.',
                                    };
                                @endphp
                                <div class="text-center">
                                    <p class="text-sm font-semibold text-admin-text">{{ $logo->state->label() }} logo</p>
                                    <p class="mt-1 text-xs text-admin-muted">{{ $logoStateCopy }}</p>
                                </div>
                            @endif
                        </div>

                        <dl class="brand-detail-profile-fields" data-screen-region="general-information">
                            <div class="brand-detail-profile-field" data-screen-region="parent-company">
                                <dt>Parent Company</dt>
                                <dd class="mt-1 break-words text-sm font-semibold text-admin-text" data-parent-company>{{ $parentCompany?->name ?? 'No Parent Company' }}</dd>
                            </div>
                            <div class="brand-detail-profile-field">
                                <dt>Country</dt>
                                <dd class="mt-1 break-words text-sm text-admin-text">{{ $countryName === null ? '—' : $countryName.' ('.$brand->country->alpha2.')' }}</dd>
                            </div>
                            <div class="brand-detail-profile-field">
                                <dt>Founded</dt>
                                <dd class="mt-1 text-sm text-admin-text">{{ $brand->founded_year ?? '—' }}</dd>
                            </div>
                            <div class="brand-detail-profile-field">
                                <dt>Primary color</dt>
                                <dd class="mt-1 flex min-w-0 items-center gap-2 text-sm text-admin-text">
                                    @if ($primaryColorIsSafe)
                                        <span class="h-5 w-5 shrink-0 rounded-admin-input border border-admin-border" style="background-color: {{ $brand->primary_color }}" aria-hidden="true"></span>
                                        <span class="break-all font-foundation-mono text-xs">{{ $brand->primary_color }}</span>
                                    @else — @endif
                                </dd>
                            </div>
                            <div class="brand-detail-profile-field">
                                <dt>Website</dt>
                                <dd class="mt-1 break-all text-sm text-admin-text">
                                    @if ($brand->website_url === null) —
                                    @elseif ($websiteIsSafe)<a href="{{ $brand->website_url }}" target="_blank" rel="noopener noreferrer" class="font-medium text-admin-primary underline decoration-admin-primary/30 underline-offset-2">{{ $websiteLabel }}</a>
                                    @else {{ $brand->website_url }} @endif
                                </dd>
                            </div>
                            <div class="contents" data-screen-region="online-presence">
                                <div class="brand-detail-profile-field">
                                    <dt>Support URL</dt>
                                    <dd class="mt-1 break-all text-sm text-admin-text">
                                        @if ($brand->support_url === null) —
                                        @elseif ($supportUrlIsSafe)<a href="{{ $brand->support_url }}" target="_blank" rel="noopener noreferrer" class="font-medium text-admin-primary underline decoration-admin-primary/30 underline-offset-2">{{ $supportUrlLabel }}</a>
                                        @else {{ $brand->support_url }} @endif
                                    </dd>
                                </div>
                                <div class="brand-detail-profile-field">
                                    <dt>Contact email</dt>
                                    <dd class="mt-1 break-all text-sm text-admin-text">{{ $brand->contact_email ?? '—' }}</dd>
                                </div>
                            </div>
                        </dl>
                    </div>
                </x-admin.card>

                <x-admin.card class="brand-detail-portfolio min-w-0" title="Product portfolio" data-screen-region="usage">
                    <div class="brand-detail-metrics">
                        <div class="brand-detail-metric">
                            <strong class="text-2xl font-semibold text-admin-text" data-products-count="{{ $productsCount }}">{{ number_format($productsCount) }}</strong>
                            <p>Products</p>
                        </div>
                        <div class="brand-detail-metric">
                            <strong class="text-2xl font-semibold text-admin-text">{{ number_format($categoryCoverage->count()) }}</strong>
                            <p>Categories</p>
                        </div>
                        <div class="brand-detail-metric">
                            <strong class="text-2xl font-semibold text-admin-text">{{ $translationSummary->total === 0 ? '—' : $translationSummary->score().'%' }}</strong>
                            <p>Translations</p>
                        </div>
                    </div>
                    <p class="mt-3 text-sm text-admin-muted" data-product-usage-copy>
                        @if ($productsCount === 0) No current canonical products reference this brand yet.
                        @elseif ($productsCount === 1) 1 current canonical product references this brand.
                        @else {{ number_format($productsCount) }} current canonical products reference this brand. @endif
                    </p>
                </x-admin.card>

                <x-admin.card class="brand-detail-products min-w-0" title="Recent products" data-screen-region="recent-products">
                    @if ($recentProducts->isEmpty())
                        <p class="text-sm text-admin-muted">No current products reference this Brand.</p>
                    @else
                        <ul class="brand-detail-product-list" data-brand-recent-products>
                            @foreach ($recentProducts as $product)
                                @php $productStatusVariant = $product->status->color() === 'gray' ? 'neutral' : $product->status->color(); @endphp
                                <li data-product-id="{{ $product->getKey() }}">
                                    <div class="min-w-0">
                                        @can('catalog.products.manage')
                                            <a href="{{ route('filament.central.resources.central-products.view', $product, absolute: false) }}" class="break-words text-sm font-semibold text-admin-text hover:text-admin-primary">{{ $product->name }}</a>
                                        @else
                                            <p class="break-words text-sm font-semibold text-admin-text">{{ $product->name }}</p>
                                        @endcan
                                        <p class="mt-1 flex min-w-0 flex-wrap gap-x-2 gap-y-1 text-xs text-admin-muted">
                                            <span class="break-all font-foundation-mono">{{ $product->model ?: $product->slug }}</span>
                                            <span>{{ $product->category?->name ?? 'Uncategorized' }}</span>
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-3">
                                        <x-admin.status-badge :label="$product->status->label()" :variant="$productStatusVariant" size="sm" />
                                        <x-ui.timestamp :value="$product->updated_at" timezone="UTC" />
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-admin.card>

                <div class="brand-detail-provenance min-w-0">
                    @include('central-admin.brands.partials.external-identities-card')
                </div>
            </div>

            <aside class="brand-detail-rail" aria-label="Brand operations" data-brand-detail-rail>
                <x-admin.card class="brand-detail-health min-w-0" title="Brand health" data-screen-region="quality-completeness">
                    <div class="space-y-admin-card">
                        <div class="flex items-end justify-between gap-4">
                            <div>
                                <p class="text-4xl font-semibold text-admin-text" data-brand-quality-score="{{ $quality->score }}">{{ $quality->score }}%</p>
                                <p class="mt-1 text-xs font-medium text-admin-muted">{{ $quality->completedChecks }} of {{ $quality->totalChecks }} checks complete</p>
                            </div>
                            <x-admin.status-badge :label="$quality->state->label()" :variant="$quality->state->badgeVariant()" size="sm" />
                        </div>
                        <div class="h-2 overflow-hidden rounded-admin-badge bg-admin-surface-muted" role="progressbar" aria-label="Brand completeness" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $quality->score }}">
                            <div class="h-full rounded-admin-badge {{ $quality->state === \App\Enums\CentralBrandQualityState::Complete ? 'bg-admin-success' : 'bg-admin-warning' }}" style="width: {{ $quality->score }}%"></div>
                        </div>
                        <div class="border-t border-admin-border pt-admin-card" data-screen-region="translation-summary">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-admin-text">Translation coverage</p>
                                    <p class="mt-1 text-xs text-admin-muted">{{ $translationSummary->complete() }} of {{ $translationSummary->total }} active locales complete</p>
                                </div>
                                <strong class="text-xl font-semibold text-admin-text">{{ $translationSummary->total === 0 ? '—' : $translationSummary->score().'%' }}</strong>
                            </div>
                            @if ($translationSummary->total > 0)
                                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                    @if ($translationSummary->approved > 0)<span class="rounded-admin-input bg-admin-success-soft px-2 py-1 text-admin-success">Approved {{ $translationSummary->approved }}</span>@endif
                                    @if ($translationSummary->humanReviewed > 0)<span class="rounded-admin-input bg-admin-surface-muted px-2 py-1 text-admin-muted">Reviewed {{ $translationSummary->humanReviewed }}</span>@endif
                                    @if ($translationSummary->machineTranslated > 0)<span class="rounded-admin-input bg-admin-info-soft px-2 py-1 text-admin-info">Machine {{ $translationSummary->machineTranslated }}</span>@endif
                                    @if ($translationSummary->missing > 0)<span class="rounded-admin-input bg-admin-warning-soft px-2 py-1 text-admin-warning">Missing {{ $translationSummary->missing }}</span>@endif
                                    @if ($translationSummary->outdated > 0)<span class="rounded-admin-input bg-admin-warning-soft px-2 py-1 text-admin-warning">Outdated {{ $translationSummary->outdated }}</span>@endif
                                </div>
                            @endif
                            @can('translations.manage')
                                <a href="{{ route('central.brands.translations.index', $brand, absolute: false) }}" class="mt-3 inline-flex text-sm font-semibold text-admin-primary underline decoration-admin-primary/30 underline-offset-2">Review translations</a>
                            @endcan
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card class="brand-detail-issues min-w-0" title="Issues" data-screen-region="quality-issues">
                    @if ($qualityIssues->isEmpty())
                        <p class="text-sm font-medium text-admin-success">No open Brand quality issues.</p>
                    @else
                        <p class="mb-2 text-xs font-medium text-admin-warning">{{ $qualityIssues->count() }} {{ $qualityIssues->count() === 1 ? 'issue needs' : 'issues need' }} attention</p>
                        <ul class="divide-y divide-admin-border" data-brand-quality-issues>
                            @foreach ($visibleQualityIssues as $issue)
                                @php
                                    $issueDomain = $qualityIssueDomain($issue);
                                @endphp
                                <li class="py-2.5 first:pt-0 last:pb-0" data-quality-issue-code="{{ $issue->issueCode?->value }}">
                                    <div class="flex min-w-0 items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-admin-text">{{ $issue->label }}</p>
                                            <p class="mt-1 text-xs leading-5 text-admin-muted">{{ $issue->description }}</p>
                                        </div>
                                        <span class="shrink-0 rounded-admin-badge bg-admin-warning-soft px-2 py-1 text-xs font-medium text-admin-warning">{{ $issueDomain }}</span>
                                    </div>
                                    @if ($issue->editorRoute !== null && $issue->editorPermission !== null && auth()->user()?->can($issue->editorPermission) === true)
                                        <a href="{{ route($issue->editorRoute, $issue->editorRouteParameters, absolute: false) }}" class="mt-1.5 inline-flex text-sm font-semibold text-admin-primary underline decoration-admin-primary/30 underline-offset-2">{{ $issue->editorLabel }}</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($remainingQualityIssues > 0)
                            <p class="mt-3 border-t border-admin-border pt-3 text-xs font-medium text-admin-muted">+{{ $remainingQualityIssues }} more {{ $remainingQualityIssues === 1 ? 'issue' : 'issues' }}</p>
                        @endif
                    @endif
                </x-admin.card>

                <x-admin.card id="classification" class="brand-detail-classification min-w-0" title="Classification" data-screen-region="classification">
                    <x-slot:actions>
                        @can('catalog.brands.manage')
                            <x-ui.button variant="secondary" aria-haspopup="dialog" aria-controls="manage-brand-tags-modal" data-admin-modal-open-target="manage-brand-tags-modal">Manage tags</x-ui.button>
                        @endcan
                    </x-slot:actions>
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-admin-muted">Derived categories</h3>
                        @if ($categoryCoverage->isEmpty())
                            <p class="mt-2 text-sm text-admin-muted">No category coverage.</p>
                        @else
                            <ul class="mt-2 flex flex-wrap gap-2" data-brand-derived-categories>
                                @foreach ($categoryCoverage->take(5) as $coverage)
                                    <li class="brand-detail-category-chip" data-category-id="{{ $coverage->categoryId }}">
                                        <span>{{ $coverage->name }}</span>
                                        <strong>{{ number_format($coverage->productsCount) }}</strong>
                                    </li>
                                @endforeach
                                @if ($categoryCoverage->count() > 5)
                                    <li class="rounded-admin-badge bg-admin-surface-muted px-2.5 py-1 text-xs font-medium text-admin-muted">+{{ $categoryCoverage->count() - 5 }} more</li>
                                @endif
                            </ul>
                        @endif
                    </div>
                    <div class="mt-admin-card border-t border-admin-border pt-admin-card">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-admin-muted">Editorial tags</h3>
                        @if ($brand->tags->isEmpty())
                            <p class="mt-2 text-sm text-admin-muted">No tags have been assigned to this Brand.</p>
                        @else
                            <div class="mt-2 flex flex-wrap gap-2" data-brand-tags>
                                @foreach ($brand->tags->take(6) as $tag)
                                    <span class="inline-flex max-w-full rounded-admin-badge bg-admin-surface-muted px-2.5 py-1 text-xs font-medium text-admin-text ring-1 ring-inset ring-admin-border">{{ $tag->name }}</span>
                                @endforeach
                                @if ($brand->tags->count() > 6)
                                    <span class="rounded-admin-badge bg-admin-surface-muted px-2.5 py-1 text-xs font-medium text-admin-muted">+{{ $brand->tags->count() - 6 }} more</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </x-admin.card>

                <x-admin.card class="brand-detail-lifecycle min-w-0" title="Lifecycle" data-screen-region="lifecycle">
                    @if ($lifecycleError)
                        <p class="mb-admin-card rounded-admin-input border border-admin-danger/30 bg-admin-danger-soft px-3 py-2 text-sm text-admin-text" role="alert" data-lifecycle-error>{{ $lifecycleError }}</p>
                    @endif
                    @can('catalog.brands.manage')
                        @switch($brand->status)
                            @case(\App\Enums\CentralBrandStatus::Draft)
                                <p class="text-sm text-admin-muted">Draft brands are not yet available for normal catalog use.</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <x-ui.button aria-haspopup="dialog" aria-controls="activate-brand-modal" data-admin-modal-open-target="activate-brand-modal">Activate Brand</x-ui.button>
                                    <x-ui.button variant="danger" aria-haspopup="dialog" aria-controls="archive-brand-modal" data-admin-modal-open-target="archive-brand-modal">Archive Brand</x-ui.button>
                                </div>
                                @break
                            @case(\App\Enums\CentralBrandStatus::Active)
                                <p class="text-sm text-admin-muted">Active brands are available for normal catalog use.</p>
                                <x-ui.button variant="danger" class="mt-3" aria-haspopup="dialog" aria-controls="archive-brand-modal" data-admin-modal-open-target="archive-brand-modal">Archive Brand</x-ui.button>
                                @break
                            @case(\App\Enums\CentralBrandStatus::Archived)
                                <p class="text-sm text-admin-muted">Archived references are retained. Restore returns this Brand to Draft.</p>
                                <x-ui.button class="mt-3" aria-haspopup="dialog" aria-controls="restore-brand-modal" data-admin-modal-open-target="restore-brand-modal">Restore Brand</x-ui.button>
                                @break
                        @endswitch
                    @endcan
                </x-admin.card>

                <x-admin.card class="brand-detail-record min-w-0" title="Record" data-screen-region="record-metadata">
                    <dl class="grid grid-cols-2 gap-admin-field">
                        <div class="min-w-0">
                            <dt class="text-xs font-medium uppercase tracking-wide text-admin-muted">Record ID</dt>
                            <dd class="mt-1 break-all font-foundation-mono text-sm text-admin-text">{{ $brand->getKey() }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs font-medium uppercase tracking-wide text-admin-muted">Created</dt>
                            <dd class="mt-1"><x-ui.timestamp :value="$brand->created_at" timezone="UTC" /></dd>
                        </div>
                        <div class="col-span-2 min-w-0">
                            <dt class="text-xs font-medium uppercase tracking-wide text-admin-muted">Updated</dt>
                            <dd class="mt-1"><x-ui.timestamp :value="$brand->updated_at" timezone="UTC" /></dd>
                        </div>
                    </dl>
                </x-admin.card>
            </aside>
        </div>

        @can('catalog.brands.manage')
        <form id="manage-brand-tags-form" method="POST" action="{{ route('central.brands.tags.update', $brand, absolute: false) }}">
            @csrf
            @method('PATCH')
        </form>
        <x-ui.modal id="manage-brand-tags-modal" title="Manage tags" :open="$tagEditorOpen">
            <x-ui.form.tag-input
                id="brand-tags-input"
                name="tags"
                label="Brand tags"
                :values="$tagEditorValues"
                :reset-values="$persistedTagEditorValues"
                help="Press Enter or use Add tag. Maximum 20 tags; names may be up to 80 characters."
                :error="$tagError"
                form="manage-brand-tags-form"
            />
            <p class="mt-3 text-xs text-admin-muted">Tags are global catalog labels. Matching names reuse the existing label regardless of casing.</p>
            <x-slot:footer>
                <div class="flex flex-wrap justify-end gap-admin-field">
                    <x-ui.button variant="secondary" data-admin-modal-close>Cancel</x-ui.button>
                    <x-ui.button type="submit" form="manage-brand-tags-form">Save tags</x-ui.button>
                </div>
            </x-slot:footer>
        </x-ui.modal>

        @if ($activeImportSources->isNotEmpty())
            <x-ui.modal id="add-brand-external-identity-modal" title="Add external identity" :open="$externalIdentityAddOpen">
                <form id="add-brand-external-identity-form" method="POST" action="{{ route('central.brands.external-identities.store', $brand, absolute: false) }}" class="space-y-admin-card">
                    @csrf
                    <input type="hidden" name="_external_identity_operation" value="add">
                    <x-ui.form.select
                        id="add-external-identity-source"
                        name="import_source_id"
                        label="Source"
                        :options="$activeSourceOptions"
                        :selected="$externalIdentityAddOpen ? old('import_source_id') : null"
                        placeholder="Select an active import source"
                        :error="$externalIdentityAddOpen ? $externalIdentityError('import_source_id') : null"
                        required
                        data-admin-modal-reset-value=""
                    />
                    <x-ui.form.input
                        id="add-external-identity-id"
                        name="external_id"
                        label="External ID"
                        :value="$externalIdentityAddOpen ? old('external_id') : ''"
                        :error="$externalIdentityAddOpen ? $externalIdentityError('external_id') : null"
                        help="Opaque, case-sensitive identifier from the selected source."
                        required
                        maxlength="255"
                        autocomplete="off"
                        data-admin-modal-reset-value=""
                    />
                    <x-ui.form.input
                        id="add-external-identity-url"
                        name="external_url"
                        type="url"
                        label="External record URL"
                        :value="$externalIdentityAddOpen ? old('external_url') : ''"
                        :error="$externalIdentityAddOpen ? $externalIdentityError('external_url') : null"
                        help="Optional public HTTP or HTTPS record URL."
                        optional
                        maxlength="2048"
                        data-admin-modal-reset-value=""
                    />
                </form>
                <x-slot:footer>
                    <div class="flex flex-wrap justify-end gap-admin-field">
                        <x-ui.button variant="secondary" data-admin-modal-close>Cancel</x-ui.button>
                        <x-ui.button type="submit" form="add-brand-external-identity-form">Add identity</x-ui.button>
                    </div>
                </x-slot:footer>
            </x-ui.modal>
        @endif

        @foreach ($brand->externalIdentities as $identity)
            @php
                $editModalOpen = $externalIdentityEditId === (int) $identity->getKey();
                $editExternalId = $editModalOpen ? old('external_id') : $identity->external_id;
                $editExternalUrl = $editModalOpen ? old('external_url') : $identity->external_url;
            @endphp
            <x-ui.modal id="edit-brand-external-identity-{{ $identity->getKey() }}-modal" title="Edit external identity" :open="$editModalOpen">
                <form id="edit-brand-external-identity-{{ $identity->getKey() }}-form" method="POST" action="{{ route('central.brands.external-identities.update', [$brand, $identity], absolute: false) }}" class="space-y-admin-card">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_external_identity_id" value="{{ $identity->getKey() }}">
                    <div class="rounded-admin-input border border-admin-border bg-admin-surface-muted px-3 py-2">
                        <p class="text-xs font-medium text-admin-muted">Source</p>
                        <p class="mt-1 text-sm font-semibold text-admin-text">{{ $identity->source->name }}</p>
                        <p class="mt-1 break-all font-foundation-mono text-xs text-admin-muted">{{ $identity->source->code }}</p>
                    </div>
                    <x-ui.form.input
                        id="edit-external-identity-{{ $identity->getKey() }}-id"
                        name="external_id"
                        label="External ID"
                        :value="$editExternalId"
                        :error="$editModalOpen ? $externalIdentityError('external_id') : null"
                        required
                        maxlength="255"
                        autocomplete="off"
                        :data-admin-modal-reset-value="$identity->external_id"
                    />
                    <x-ui.form.input
                        id="edit-external-identity-{{ $identity->getKey() }}-url"
                        name="external_url"
                        type="url"
                        label="External record URL"
                        :value="$editExternalUrl"
                        :error="$editModalOpen ? $externalIdentityError('external_url') : null"
                        optional
                        maxlength="2048"
                        :data-admin-modal-reset-value="$identity->external_url ?? ''"
                    />
                </form>
                <x-slot:footer>
                    <div class="flex flex-wrap justify-end gap-admin-field">
                        <x-ui.button variant="secondary" data-admin-modal-close>Cancel</x-ui.button>
                        <x-ui.button type="submit" form="edit-brand-external-identity-{{ $identity->getKey() }}-form">Save identity</x-ui.button>
                    </div>
                </x-slot:footer>
            </x-ui.modal>

            <form id="remove-brand-external-identity-{{ $identity->getKey() }}-form" method="POST" action="{{ route('central.brands.external-identities.destroy', [$brand, $identity], absolute: false) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
            <x-admin.confirmation-modal
                id="remove-brand-external-identity-{{ $identity->getKey() }}-modal"
                title="Remove external identity?"
                message="This removes only the Brand linkage. It does not delete the ImportSource."
                confirm-label="Remove identity"
                confirm-form="remove-brand-external-identity-{{ $identity->getKey() }}-form"
                variant="danger"
                :open="false"
            >
                <p class="font-semibold">{{ $identity->source->name }}</p>
                <p class="mt-1 break-all font-foundation-mono">{{ $identity->external_id }}</p>
            </x-admin.confirmation-modal>
        @endforeach

        @if ($brand->status === \App\Enums\CentralBrandStatus::Draft)
            <form id="activate-brand-form" method="POST" action="{{ route('central.brands.activate', $brand, absolute: false) }}" class="hidden">@csrf</form>
            <x-admin.confirmation-modal
                id="activate-brand-modal"
                :title="'Activate '.$brand->name.'?'"
                message="This brand will become available for normal catalog use."
                confirm-label="Activate Brand"
                confirm-form="activate-brand-form"
                :open="false"
            />
        @endif

        @if (in_array($brand->status, [\App\Enums\CentralBrandStatus::Draft, \App\Enums\CentralBrandStatus::Active], true))
            <form id="archive-brand-form" method="POST" action="{{ route('central.brands.archive', $brand, absolute: false) }}" class="hidden">@csrf</form>
            <x-admin.confirmation-modal
                id="archive-brand-modal"
                :title="'Archive '.$brand->name.'?'"
                message="Archived brands remain in existing references but should not be used for new catalog relationships. You can restore the brand later."
                confirm-label="Archive Brand"
                confirm-form="archive-brand-form"
                variant="danger"
                :open="false"
            />
        @endif

        @if ($brand->status === \App\Enums\CentralBrandStatus::Archived)
            <form id="restore-brand-form" method="POST" action="{{ route('central.brands.restore', $brand, absolute: false) }}" class="hidden">@csrf</form>
            <x-admin.confirmation-modal
                id="restore-brand-modal"
                :title="'Restore '.$brand->name.'?'"
                message="The brand will return to Draft and must be activated separately before normal use."
                confirm-label="Restore Brand"
                confirm-form="restore-brand-form"
                :open="false"
            />
        @endif
        @endcan
    </div>
@endsection
