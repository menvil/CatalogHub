@extends('layouts.central-admin', ['activeNav' => 'categories', 'pageTitle' => $detail->category->name])

@section('breadcrumbs')
    <a href="{{ route('central.categories.index') }}">Categories</a>
    @foreach ($detail->ancestors as $ancestor)
        <span aria-hidden="true">/</span><a href="{{ route('central.categories.show', ['category' => $ancestor['id'], 'locale' => $detail->translations->localeId]) }}">{{ $ancestor['name'] }}</a>
    @endforeach
    <span aria-hidden="true">/</span><span aria-current="page">{{ $detail->category->name }}</span>
@endsection

@section('content')
<div class="category-detail-page" data-screen-id="CA-017" data-fixture-version="category-detail-v1">
    <header class="category-detail-heading">
        <div class="min-w-0">
            <div class="category-detail-title"><span class="category-list-icon"><x-ui.icon name="squares-2x2" /></span><h1 class="text-foundation-heading font-semibold text-admin-text">{{ $detail->category->name }}</h1></div>
            <div class="category-detail-states">
                <span data-category-lifecycle>Category: <x-ui.status-badge :label="$detail->category->status->label()" :tone="$detail->category->status->value === 'active' ? 'success' : 'neutral'" /></span>
                <span data-category-schema-state>Schema: <x-ui.status-badge :label="$detail->category->schema_status->label()" :tone="$detail->category->schema_status->value === 'approved' ? 'success' : 'neutral'" /></span>
            </div>
            <p>Canonical source reference · Category overview</p>
        </div>
        <div class="category-detail-actions" data-page-actions>
            @if ($editUrl)<x-ui.button :href="$editUrl">Edit Category</x-ui.button>@endif
            @foreach ($commands as $command)
                <x-ui.button :variant="$command === 'archive' ? 'danger' : 'secondary'" aria-haspopup="dialog" aria-controls="{{ $command }}-category-detail-modal" data-admin-modal-open-target="{{ $command }}-category-detail-modal">{{ ucfirst($command) }} Category</x-ui.button>
            @endforeach
        </div>
    </header>
    @if (session('success'))<p role="status" class="text-admin-success">{{ session('success') }}</p>@endif
    @if ($errors->any())<p role="alert" class="text-admin-danger">{{ $errors->first() }}</p>@endif
    <p class="sr-only" role="status" aria-live="polite" data-category-copy-feedback></p>

    <div class="category-detail-grid">
        <x-admin.card title="Information" class="category-detail-information" data-screen-region="identity">
            <dl class="category-detail-facts">
                <div><dt>Category ID</dt><dd><code data-category-id>{{ $detail->category->id }}</code><button type="button" class="category-detail-copy" aria-label="Copy Category ID" data-category-copy="{{ $detail->category->id }}">Copy</button></dd></div>
                <div><dt>Slug</dt><dd><code data-category-slug>{{ $detail->category->slug }}</code><button type="button" class="category-detail-copy" aria-label="Copy Category slug" data-category-copy="{{ $detail->category->slug }}">Copy</button></dd></div>
                <div><dt>Parent Category</dt><dd data-category-parent>
                    @if (! $detail->hierarchyAvailable)Hierarchy unavailable
                    @elseif ($detail->ancestors)
                        <a href="{{ route('central.categories.show', ['category' => $detail->ancestors[array_key_last($detail->ancestors)]['id'], 'locale' => $detail->translations->localeId]) }}">{{ $detail->ancestors[array_key_last($detail->ancestors)]['name'] }}</a>
                    @else Root Category @endif
                </dd></div>
                <div><dt>Created</dt><dd><time datetime="{{ $detail->category->created_at?->toAtomString() }}">{{ $detail->category->created_at?->format('d M Y, H:i') ?? 'Not recorded' }}</time></dd></div>
                <div><dt>Updated</dt><dd><time datetime="{{ $detail->category->updated_at?->toAtomString() }}">{{ $detail->category->updated_at?->format('d M Y, H:i') ?? 'Not recorded' }}</time></dd></div>
                <div><dt>Created By</dt><dd>{{ $detail->activity->createdBy }}</dd></div>
                <div><dt>Last identity update by</dt><dd>{{ $detail->activity->lastIdentityUpdateBy }}</dd></div>
            </dl>
            <section class="category-detail-description" aria-labelledby="category-description-heading">
                <h3 id="category-description-heading">Localized description</h3>
                @if ($detail->translations->localeId !== null)
                    <form method="GET" action="{{ route('central.categories.show', $detail->category) }}" class="category-detail-locale-form">
                        <x-ui.form.select id="category-detail-locale" name="locale" label="Description Locale" :options="$detail->translations->localeOptions" :selected="$detail->translations->localeId" />
                        <x-ui.button type="submit" variant="secondary">Apply Locale</x-ui.button>
                    </form>
                    <p class="category-detail-note">{{ $detail->translations->localeCode }} translation: <strong data-selected-translation-status>{{ $detail->translations->statusLabel() }}</strong></p>
                    @if ($detail->translations->description?->value !== null)
                        <p class="category-detail-description-text" data-category-description>{{ $detail->translations->description->value }}</p>
                        @if ($detail->translations->description->source === 'fallback_locale')
                            <p class="category-detail-note" data-description-provenance>Showing {{ $detail->translations->description->locale }} fallback — {{ $detail->translations->localeCode }} translation is {{ strtolower($detail->translations->statusLabel()) }}. Fallback text does not change the exact Locale status.</p>
                        @endif
                    @else <p class="category-detail-note">No localized description is available.</p> @endif
                @else <p class="category-detail-note">No active Locales. Source reference identity remains available.</p> @endif
            </section>
        </x-admin.card>

        <x-admin.card title="Schema Summary" data-screen-region="schema-summary">
            <dl class="category-detail-counts">
                @foreach (['sections' => 'Sections', 'attributes' => 'Assigned Attributes', 'required' => 'Required Attributes', 'facets' => 'Canonical Facets', 'comparison' => 'Configured comparison attributes'] as $key => $label)
                    <div><dt>{{ $label }}</dt><dd data-detail-count="{{ $key }}">{{ number_format($detail->schema->$key) }}</dd></div>
                @endforeach
            </dl>
            <dl class="category-detail-facts category-detail-revisions">
                <div><dt>Current revision</dt><dd data-schema-revision>{{ $detail->category->schema_revision }}</dd></div>
                <div><dt>Reviewed revision</dt><dd>{{ $detail->category->schema_reviewed_revision ?? 'Not reviewed' }}</dd></div>
                <div><dt>Reviewed by</dt><dd>{{ $detail->schema->reviewer ?? 'Not recorded' }} @if ($detail->category->schema_reviewed_at)<time datetime="{{ $detail->category->schema_reviewed_at->toAtomString() }}">· {{ $detail->category->schema_reviewed_at->format('d M Y, H:i') }}</time>@endif</dd></div>
                <div><dt>Approved revision</dt><dd>{{ $detail->category->schema_approved_revision ?? 'Not approved' }}</dd></div>
                <div><dt>Approved by</dt><dd>{{ $detail->schema->approver ?? 'Not recorded' }} @if ($detail->category->schema_approved_at)<time datetime="{{ $detail->category->schema_approved_at->toAtomString() }}">· {{ $detail->category->schema_approved_at->format('d M Y, H:i') }}</time>@endif</dd></div>
            </dl>
            <section class="category-detail-validation" aria-label="Schema validation">
                <h3>Validation: {{ $detail->schema->issueCount }} {{ $detail->schema->issueCount === 1 ? 'issue' : 'issues' }}</h3>
                @if ($detail->schema->issues)
                    <ul>@foreach ($detail->schema->issues as $issue)<li><strong>{{ ucfirst($issue->severity->value) }}:</strong> {{ $issue->message }}</li>@endforeach</ul>
                    @if ($detail->schema->issueCount > count($detail->schema->issues))<p class="category-detail-note">{{ $detail->schema->issueCount - count($detail->schema->issues) }} additional issues.</p>@endif
                @else <p class="category-detail-note">No issues reported by the current schema validator.</p> @endif
            </section>
            @if ($schemaUrl)<x-slot:footer><a href="{{ $schemaUrl }}" class="category-detail-link">View Schema</a></x-slot:footer>
            @endif
        </x-admin.card>

        <x-admin.card title="Usage" data-screen-region="usage">
            <dl class="category-detail-counts">
                <div><dt>Direct Products</dt><dd data-detail-count="products">{{ number_format($detail->products) }}</dd></div>
                <div><dt>Selected by Sites</dt><dd data-detail-count="sites">{{ number_format($detail->selectedSites) }}</dd></div>
            </dl>
            <p class="category-detail-note">Products include every persisted lifecycle state assigned directly to this Category. Child Category Products are excluded.</p>
            <p class="category-detail-note">Site selections include Draft, Active and Suspended Sites, including disabled or hidden selections. Archived and deleted Sites are excluded.</p>
        </x-admin.card>

        <x-admin.card title="Selected by Sites" data-screen-region="site-selection">
            @if ($detail->sites)
                <ul class="category-detail-sites">
                    @foreach ($detail->sites as $site)<li><strong>{{ $site['name'] }}</strong><span>{{ $site['status'] }}</span></li>@endforeach
                </ul>
                @if ($detail->selectedSites > count($detail->sites))<p class="category-detail-note">{{ $detail->selectedSites - count($detail->sites) }} more selected Sites.</p>@endif
            @else <p class="category-detail-note">No eligible Sites have selected this Category.</p> @endif
            <p class="category-detail-note">Selections are read-only relationship context, independent of public availability.</p>
        </x-admin.card>

        <x-admin.card title="Translations" data-screen-region="translations">
            @if ($detail->translations->total() > 0)
                <p class="category-detail-coverage" data-translation-coverage>{{ $detail->translations->covered }}/{{ $detail->translations->total() }} covered · {{ $detail->translations->percentage() }}%</p>
                <dl class="category-detail-counts">
                    <div><dt>Active Locales</dt><dd>{{ $detail->translations->total() }}</dd></div>
                    <div><dt>Covered</dt><dd>{{ $detail->translations->covered }}</dd></div>
                    <div><dt>Missing</dt><dd>{{ $detail->translations->missing }}</dd></div>
                    <div><dt>Outdated</dt><dd>{{ $detail->translations->outdated }}</dd></div>
                </dl>
                <p class="category-detail-note">Covered: Machine translated, Human reviewed or Approved. Absent translations are Missing. Outdated remains separate.</p>
            @else <p class="category-detail-note">No active Locales. Translation coverage is unavailable.</p> @endif
            @if ($translationUrl)<x-slot:footer><a href="{{ $translationUrl }}" class="category-detail-link">Edit {{ $detail->translations->localeCode }} translation</a></x-slot:footer>
            @endif
        </x-admin.card>

        <x-admin.card title="Recent Activity" data-screen-region="recent-activity">
            @if ($detail->activity->events)
                <ol class="category-detail-activity">
                    @foreach ($detail->activity->events as $event)
                        <li data-activity-id="{{ $event->id }}"><h3>{{ $event->label }}</h3><p>{{ $event->summary }}</p><span>{{ $event->actor }}</span><time datetime="{{ $event->at->toAtomString() }}">{{ $event->at->format('d M Y, H:i') }}</time></li>
                    @endforeach
                </ol>
                <p class="category-detail-note">Latest {{ count($detail->activity->events) }} Category-scoped events.</p>
            @else <p class="category-detail-note">No recorded Category activity.</p> @endif
        </x-admin.card>
    </div>
    <a href="{{ route('central.categories.index', ['locale' => $detail->translations->localeId]) }}" class="category-detail-link">Back to Categories</a>
    @foreach ($commands as $command)
        <form id="{{ $command }}-category-detail-form" method="POST" action="{{ route('central.categories.'.$command, $detail->category) }}" class="hidden">
            @csrf
            <input type="hidden" name="context" value="detail">
            <input type="hidden" name="expected_status" value="{{ $detail->category->status->value }}">
            @if ($detail->translations->localeId !== null)<input type="hidden" name="locale" value="{{ $detail->translations->localeId }}">@endif
            @if ($command === 'archive')<input type="hidden" name="confirmed" value="1">@endif
        </form>
        <x-admin.confirmation-modal id="{{ $command }}-category-detail-modal" :title="ucfirst($command).' '.$detail->category->name.'?'" :message="match ($command) { 'archive' => 'Existing Products, schema assignments, children and Site selections are retained.', 'restore' => 'The Category returns to Draft. Activation is a separate action.', default => 'The Category becomes Active for canonical catalog use.' }" :confirm-label="ucfirst($command).' Category'" confirm-form="{{ $command }}-category-detail-form" :variant="$command === 'archive' ? 'danger' : 'default'" :open="false" />
    @endforeach
</div>
@endsection
