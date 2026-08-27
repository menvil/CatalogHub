@extends('layouts.central-admin', ['activeNav' => 'brands', 'pageTitle' => 'Brand Media / Identity'])

@section('breadcrumbs')
    <a href="{{ route('filament.central.pages.home', absolute: false) }}" class="font-medium hover:text-admin-text">Central Admin</a><span aria-hidden="true">/</span>
    <a href="{{ route('central.brands.index', absolute: false) }}" class="font-medium hover:text-admin-text">Brands</a><span aria-hidden="true">/</span>
    <a href="{{ route('central.brands.show', $brand, absolute: false) }}" class="font-medium hover:text-admin-text">{{ $brand->name }}</a><span aria-hidden="true">/</span><span aria-current="page">Media</span>
@endsection

@section('content')
    @php
        $lifecycleVariant = $brand->status->color() === 'gray' ? 'neutral' : $brand->status->color();
        $assetCreated = $asset?->created_at?->toImmutable()->utc()->format('Y-m-d H:i \U\T\C');
        $assetUpdated = $asset?->updated_at?->toImmutable()->utc()->format('Y-m-d H:i \U\T\C');
        $formatBytes = static function (?int $bytes): string {
            if ($bytes === null) {
                return '—';
            }
            if ($bytes < 1024) {
                return number_format($bytes).' B';
            }
            if ($bytes < 1024 * 1024) {
                return number_format($bytes / 1024, 1).' KB';
            }

            return number_format($bytes / (1024 * 1024), 1).' MB';
        };
    @endphp

    <div class="min-w-0 space-y-admin-section" data-brand-media-fixture="brand-media-v2">
        <x-admin.page-header
            screen-id="CA-014"
            :show-screen-id="false"
            title="Brand Media / Identity"
            :description="'Manage '.$brand->name.'\'s canonical global identity asset through Shared Media.'"
            :breadcrumbs="[]"
        >
            <x-slot:actions>
                <x-admin.status-badge :label="$brand->status->label()" :variant="$lifecycleVariant" />
                <x-ui.button variant="secondary" :href="route('central.brands.show', $brand, absolute: false)">View Brand</x-ui.button>
            </x-slot:actions>
        </x-admin.page-header>

        @include('central-admin.brands.partials.subnav', ['active' => 'media'])

        <div class="grid min-w-0 gap-admin-section xl:grid-cols-[minmax(0,1.45fr)_minmax(20rem,0.75fr)]">
            <x-admin.card
                title="Current identity media"
                description="The canonical global primary logo used by Brand overview and completeness."
                data-screen-region="current-identity-media"
            >
                <div class="flex min-w-0 flex-wrap items-center gap-2 border-b border-admin-border pb-admin-card">
                    <p class="mr-auto text-sm font-semibold text-admin-text">Primary logo</p>
                    <x-admin.status-badge label="Global" variant="info" size="sm" />
                    <x-admin.status-badge label="Primary" variant="neutral" size="sm" />
                    <x-admin.status-badge
                        :label="$logo->state->label()"
                        :variant="$logo->state->badgeVariant()"
                        size="sm"
                        data-logo-delivery-state="{{ $logo->state->value }}"
                    />
                </div>

                <div class="mt-admin-card" data-brand-media-role="brand_logo">
                    @if ($logo->state === \App\Enums\MediaDeliveryState::Ready && $logo->url !== null)
                        <div class="flex min-h-64 items-center justify-center overflow-hidden rounded-admin-card border border-admin-border bg-[linear-gradient(45deg,#f4f4f5_25%,transparent_25%),linear-gradient(-45deg,#f4f4f5_25%,transparent_25%)] bg-[size:20px_20px] p-6" data-logo-preview>
                            <img class="max-h-64 max-w-full object-contain" src="{{ $logo->url }}" alt="{{ $brand->name }} logo">
                        </div>
                        <p class="mt-3 text-sm text-admin-muted">
                            Delivered from {{ $logo->variantName ? str_replace('_', ' ', $logo->variantName) : 'the normalized master' }}.
                        </p>
                    @elseif ($logo->state === \App\Enums\MediaDeliveryState::Missing)
                        <x-admin.empty-state
                            title="No canonical logo assigned"
                            description="Assigning a global primary Brand logo resolves the brand_logo_missing completeness issue."
                            icon="+"
                        />
                    @else
                        @php
                            $stateCopy = match ($logo->state) {
                                \App\Enums\MediaDeliveryState::Processing => 'The assigned asset is still processing. A usable file is not available yet.',
                                \App\Enums\MediaDeliveryState::Failed => 'Processing failed for the assigned asset. Replace it with a valid image or choose another shared asset.',
                                default => 'The assignment exists, but neither a ready semantic variant nor the normalized master can be delivered.',
                            };
                        @endphp
                        <x-admin.empty-state
                            :title="$logo->state->label().' logo'"
                            :description="$stateCopy"
                            :variant="$logo->state === \App\Enums\MediaDeliveryState::Failed ? 'error' : 'warning'"
                            icon="!"
                        />
                    @endif
                </div>

                @can('catalog.brands.manage')
                    <form
                        class="mt-6 min-w-0 space-y-3 rounded-admin-card border border-admin-border bg-admin-surface-muted p-admin-card"
                        method="POST"
                        enctype="multipart/form-data"
                        action="{{ route('central.brands.media.logo.store', $brand) }}"
                        data-logo-upload-form
                    >
                        @csrf
                        <div>
                            <label class="block text-sm font-semibold text-admin-text" for="logo">{{ $asset ? 'Replace with a new upload' : 'Upload a primary logo' }}</label>
                            <p class="mt-1 text-sm text-admin-muted">The current assignment changes only after secure ingest succeeds.</p>
                        </div>
                        <input
                            id="logo"
                            name="logo"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="block w-full min-w-0 rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text file:mr-3 file:rounded-admin-input file:border-0 file:bg-admin-primary-soft file:px-3 file:py-2 file:font-medium file:text-admin-primary"
                            required
                            aria-describedby="logo-help @error('logo') logo-error @enderror"
                        >
                        <p id="logo-help" class="text-sm text-admin-muted">JPEG, PNG or WebP · maximum 20 MB · maximum 8000 px per side and 16 MP.</p>
                        @error('logo')
                            <p id="logo-error" class="text-sm font-medium text-admin-danger" role="alert">{{ $message }}</p>
                        @enderror
                        <div class="flex flex-wrap gap-admin-field">
                            <x-ui.button type="submit">{{ $asset ? 'Replace logo' : 'Upload logo' }}</x-ui.button>
                        </div>
                    </form>

                    @if ($assignment)
                        <div class="mt-4 border-t border-admin-border pt-4">
                            <form id="remove-brand-logo" method="POST" action="{{ route('central.brands.media.logo.destroy', $brand) }}">
                                @csrf
                                @method('DELETE')
                                <x-ui.button
                                    variant="danger"
                                    aria-haspopup="dialog"
                                    aria-controls="remove-brand-logo-modal"
                                    data-admin-modal-open-target="remove-brand-logo-modal"
                                >Remove assignment</x-ui.button>
                            </form>
                            <x-admin.confirmation-modal
                                id="remove-brand-logo-modal"
                                :title="'Remove the canonical logo from '.$brand->name.'?'"
                                message="Only the Brand assignment is removed. The shared MediaAsset and its files remain available to other assignments."
                                confirm-label="Remove assignment"
                                confirm-form="remove-brand-logo"
                                variant="danger"
                                :open="false"
                            />
                        </div>
                    @endif
                @endcan
            </x-admin.card>

            <x-admin.card
                title="Asset information"
                description="Safe master metadata; storage paths and credentials are never shown."
                data-screen-region="asset-information"
            >
                @if ($asset)
                    <dl class="grid min-w-0 grid-cols-1 gap-x-4 gap-y-4 text-sm sm:grid-cols-[8rem_minmax(0,1fr)] xl:grid-cols-1 2xl:grid-cols-[8rem_minmax(0,1fr)]">
                        <div class="contents"><dt class="text-admin-muted">Filename</dt><dd class="min-w-0 break-all font-medium text-admin-text">{{ $asset->original_filename ?: 'Unnamed media asset' }}</dd></div>
                        <div class="contents"><dt class="text-admin-muted">MIME type</dt><dd class="break-all text-admin-text">{{ $asset->mime_type ?: '—' }}</dd></div>
                        <div class="contents"><dt class="text-admin-muted">Dimensions</dt><dd class="text-admin-text">{{ $asset->width && $asset->height ? number_format($asset->width).' × '.number_format($asset->height).' px' : '—' }}</dd></div>
                        <div class="contents"><dt class="text-admin-muted">File size</dt><dd class="text-admin-text">{{ $formatBytes($asset->file_size) }}</dd></div>
                        <div class="contents"><dt class="text-admin-muted">Asset status</dt><dd><x-admin.status-badge :label="ucfirst((string) $asset->status)" :variant="$logo->state->badgeVariant()" size="sm" /></dd></div>
                        <div class="contents"><dt class="text-admin-muted">Source</dt><dd class="break-all text-admin-text">{{ $asset->source ?: '—' }}</dd></div>
                        <div class="contents"><dt class="text-admin-muted">Created</dt><dd class="text-admin-text">{{ $assetCreated ?: '—' }}</dd></div>
                        <div class="contents"><dt class="text-admin-muted">Updated</dt><dd class="text-admin-text">{{ $assetUpdated ?: '—' }}</dd></div>
                        <div class="contents"><dt class="text-admin-muted">Asset ID</dt><dd class="break-all font-foundation-mono text-xs text-admin-text">{{ $asset->uuid }}</dd></div>
                        <div class="contents"><dt class="text-admin-muted">Assignment</dt><dd class="text-admin-text">central_brand · brand_logo</dd></div>
                    </dl>
                    @can('media.manage')
                        <div class="mt-5 border-t border-admin-border pt-4">
                            <x-ui.button variant="secondary" :href="route('central.media.show', $asset, absolute: false)">Open MediaAsset</x-ui.button>
                        </div>
                    @endcan
                @else
                    <x-admin.empty-state
                        title="No asset metadata"
                        description="Upload or assign a compatible shared asset to inspect its safe metadata here."
                    />
                @endif
            </x-admin.card>
        </div>

        <x-admin.card
            title="Generated variants"
            description="Read-only Shared Media outputs for the Brand logo profile."
            data-screen-region="generated-variants"
        >
            @if ($variants === [])
                <x-admin.empty-state
                    title="No generated variants recorded"
                    :description="$asset ? 'The normalized master can remain usable while asynchronous variant generation starts.' : 'Variants appear after a Brand logo is assigned and processed.'"
                />
            @else
                <div class="grid min-w-0 gap-admin-field md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($variants as $variant)
                        <article class="min-w-0 rounded-admin-card border border-admin-border bg-admin-surface-muted p-admin-card" data-logo-variant="{{ $variant->name }}">
                            <div class="flex min-w-0 items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="break-all font-foundation-mono text-sm font-semibold text-admin-text">{{ $variant->name }}</h3>
                                    <p class="mt-1 text-xs text-admin-muted">{{ $variant->format ? strtoupper($variant->format) : 'Format pending' }}</p>
                                </div>
                                <x-admin.status-badge :label="$variant->state->label()" :variant="$variant->state->badgeVariant()" size="sm" />
                            </div>
                            <dl class="mt-4 grid grid-cols-[6rem_minmax(0,1fr)] gap-y-2 text-sm">
                                <dt class="text-admin-muted">Dimensions</dt><dd class="text-admin-text">{{ $variant->width && $variant->height ? number_format($variant->width).' × '.number_format($variant->height).' px' : '—' }}</dd>
                                <dt class="text-admin-muted">File size</dt><dd class="text-admin-text">{{ $formatBytes($variant->fileSize) }}</dd>
                            </dl>
                            @if ($variant->url)
                                <a href="{{ $variant->url }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex text-sm font-semibold text-admin-primary hover:underline">Open variant</a>
                            @elseif ($variant->state === \App\Enums\MediaDeliveryState::Failed)
                                <p class="mt-4 text-xs text-admin-danger">No Brand-only retry operation exists; replace the asset if the master is also unusable.</p>
                            @elseif ($variant->state === \App\Enums\MediaDeliveryState::Unavailable)
                                <p class="mt-4 text-xs text-admin-muted">The variant record exists, but its file cannot be delivered.</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </x-admin.card>

        @if ($availableAssets !== null)
            <x-admin.card
                title="Reuse an existing MediaAsset"
                description="A bounded view of active JPEG, PNG and WebP assets from Shared Media."
                data-screen-region="asset-reuse"
            >
                <form method="GET" action="{{ route('central.brands.media', $brand) }}" class="flex min-w-0 flex-col gap-admin-field sm:flex-row sm:items-end">
                    <label class="min-w-0 flex-1 text-sm font-medium text-admin-text">
                        Search shared media
                        <input
                            type="search"
                            name="asset_search"
                            value="{{ $assetSearch }}"
                            maxlength="255"
                            class="mt-1 w-full min-w-0 rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2"
                            placeholder="Filename, checksum or asset ID"
                        >
                    </label>
                    <x-ui.button type="submit" variant="secondary">Search assets</x-ui.button>
                </form>

                @error('media_asset_id')
                    <p class="mt-3 rounded-admin-input border border-admin-danger/30 bg-admin-danger-soft px-3 py-2 text-sm font-medium text-admin-danger" role="alert">{{ $message }}</p>
                @enderror

                @if ($availableAssets->isEmpty())
                    <div class="mt-4">
                        <x-admin.empty-state
                            title="No compatible shared assets"
                            description="Try another search or use the secure upload above."
                        />
                    </div>
                @else
                    <div class="mt-4 grid min-w-0 gap-admin-field sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($availableAssets as $candidate)
                            @php
                                $candidateLogo = $availableLogos->get((int) $candidate->getKey());
                                $isCurrent = $asset?->is($candidate) === true;
                            @endphp
                            <article class="min-w-0 overflow-hidden rounded-admin-card border border-admin-border bg-admin-surface-muted">
                                <div class="flex aspect-[3/2] items-center justify-center border-b border-admin-border bg-admin-surface p-4">
                                    @if ($candidateLogo?->url)
                                        <img src="{{ $candidateLogo->url }}" alt="" class="max-h-full max-w-full object-contain" loading="lazy">
                                    @else
                                        <span class="text-sm font-medium text-admin-muted">Unavailable preview</span>
                                    @endif
                                </div>
                                <div class="min-w-0 space-y-3 p-3">
                                    <div class="min-w-0">
                                        <h3 class="break-all text-sm font-semibold text-admin-text">{{ $candidate->original_filename ?: $candidate->uuid }}</h3>
                                        <p class="mt-1 break-all text-xs text-admin-muted">{{ $candidate->mime_type }} · {{ $candidate->width }} × {{ $candidate->height }} px</p>
                                    </div>
                                    <form method="POST" action="{{ route('central.brands.media.logo.assign', $brand) }}">
                                        @csrf
                                        <input type="hidden" name="media_asset_id" value="{{ $candidate->id }}">
                                        <x-ui.button
                                            type="submit"
                                            variant="secondary"
                                            :disabled="$isCurrent || $candidateLogo?->url === null"
                                        >{{ $isCurrent ? 'Current asset' : 'Assign as logo' }}</x-ui.button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="mt-5 min-w-0 overflow-x-auto">{{ $availableAssets->links() }}</div>
                @endif
            </x-admin.card>
        @endif
    </div>
@endsection
