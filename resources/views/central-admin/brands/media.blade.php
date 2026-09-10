@extends('layouts.central-admin', ['activeNav' => 'brands', 'pageTitle' => 'Brand Media'])

@section('breadcrumbs')
    <a href="{{ route('filament.central.pages.home', absolute: false) }}" class="font-medium hover:text-admin-text">Central Admin</a><span aria-hidden="true">/</span>
    <a href="{{ route('central.brands.index', absolute: false) }}" class="font-medium hover:text-admin-text">Brands</a><span aria-hidden="true">/</span>
    <a href="{{ route('central.brands.show', $brand, absolute: false) }}" class="font-medium hover:text-admin-text">{{ $brand->name }}</a><span aria-hidden="true">/</span><span aria-current="page">Media</span>
@endsection

@section('content')
    @php
        $assetCreated = $asset?->created_at?->toImmutable()->utc()->format('Y-m-d H:i \U\T\C');
        $assetUpdated = $asset?->updated_at?->toImmutable()->utc()->format('Y-m-d H:i \U\T\C');
        $uploadModalOpen = $errors->has('logo');
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
        $assetStatusVariant = match ((string) $asset?->status) {
            'active' => 'success',
            'pending', 'processing' => 'warning',
            'failed' => 'danger',
            default => 'neutral',
        };
    @endphp

    <div class="min-w-0 space-y-admin-section" data-brand-media-fixture="brand-media-v4">
        <x-admin.page-header
            screen-id="CA-014"
            :show-screen-id="false"
            title="Brand Media"
            :description="'Manage '.$brand->name.'\'s canonical global logo through Shared Media.'"
            :breadcrumbs="[]"
        >
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('central.brands.show', $brand, absolute: false)">View Brand</x-ui.button>
            </x-slot:actions>
        </x-admin.page-header>

        @include('central-admin.brands.partials.subnav', ['active' => 'media'])

        <x-admin.card
            title="Primary logo"
            description="The canonical global logo used across the central catalog."
            data-screen-region="brand-logo-workspace"
        >
            <x-slot:actions>
                @if ($assignment)
                    <x-admin.status-badge label="Global" variant="info" size="sm" />
                    <x-admin.status-badge label="Primary" variant="neutral" size="sm" />
                @endif
                <x-admin.status-badge
                    :label="$logo->state->label()"
                    :variant="$logo->state->badgeVariant()"
                    size="sm"
                    data-logo-delivery-state="{{ $logo->state->value }}"
                />
            </x-slot:actions>

            <div class="grid min-w-0 items-start gap-admin-section lg:grid-cols-[minmax(0,1.8fr)_minmax(15rem,0.82fr)]" data-brand-media-role="brand_logo">
                <div class="min-w-0" data-screen-region="primary-logo">
                    @if ($logo->state === \App\Enums\MediaDeliveryState::Ready && $logo->url !== null)
                        <div class="flex h-48 items-center justify-center overflow-hidden rounded-admin-card border border-admin-border bg-admin-surface-muted p-5 sm:h-56 lg:h-64 lg:p-8" data-logo-preview>
                            <img class="h-full w-full object-contain" src="{{ $logo->url }}" alt="{{ $brand->name }} logo">
                        </div>
                        <p class="mt-3 text-xs text-admin-muted">
                            Displaying {{ $logo->variantName ? str_replace('_', ' ', $logo->variantName) : 'the normalized master' }}.
                        </p>
                    @elseif ($logo->state === \App\Enums\MediaDeliveryState::Missing)
                        <div class="flex min-h-48 items-center justify-center rounded-admin-card border border-dashed border-admin-border bg-admin-surface-muted px-6 py-8 text-center sm:min-h-56 lg:min-h-64" data-logo-empty-state>
                            <div class="max-w-md">
                                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full border border-admin-border bg-admin-surface text-xl text-admin-muted" aria-hidden="true">+</span>
                                <h2 class="mt-4 text-base font-semibold text-admin-text">No primary logo assigned</h2>
                                <p class="mt-2 text-sm text-admin-muted">Upload a logo or choose a compatible image already available in Shared Media.</p>
                            </div>
                        </div>
                    @else
                        @php
                            $stateCopy = match ($logo->state) {
                                \App\Enums\MediaDeliveryState::Processing => 'The assigned asset is still processing. The current assignment remains in place while a usable file becomes available.',
                                \App\Enums\MediaDeliveryState::Failed => 'Processing failed for the assigned asset. Replace it with a valid image or choose another shared asset.',
                                default => 'The assignment exists, but neither a ready semantic variant nor the normalized master can be delivered.',
                            };
                        @endphp
                        <div @class([
                            'flex min-h-48 items-center justify-center rounded-admin-card border px-6 py-8 text-center sm:min-h-56 lg:min-h-64',
                            'border-admin-danger/30 bg-admin-danger-soft' => $logo->state === \App\Enums\MediaDeliveryState::Failed,
                            'border-admin-warning/30 bg-admin-warning-soft' => $logo->state !== \App\Enums\MediaDeliveryState::Failed,
                        ]) data-logo-recovery-state>
                            <div class="max-w-lg">
                                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full border border-current/20 bg-admin-surface text-lg font-semibold" aria-hidden="true">!</span>
                                <h2 class="mt-4 text-base font-semibold text-admin-text">{{ $logo->state->label() }} logo</h2>
                                <p class="mt-2 text-sm text-admin-muted">{{ $stateCopy }}</p>
                            </div>
                        </div>
                    @endif

                    @can('catalog.brands.manage')
                        <div class="mt-4 flex min-w-0 flex-col gap-admin-field border-t border-admin-border pt-4 sm:flex-row sm:flex-wrap sm:items-center" data-logo-actions>
                            <x-ui.button
                                class="w-full sm:w-auto"
                                aria-haspopup="dialog"
                                aria-controls="replace-brand-logo-modal"
                                data-admin-modal-open-target="replace-brand-logo-modal"
                            >{{ $asset ? 'Replace logo' : 'Upload logo' }}</x-ui.button>

                            @can('media.manage')
                                <x-ui.button
                                    class="w-full sm:w-auto"
                                    variant="secondary"
                                    :href="route('central.brands.media', ['brand' => $brand, 'picker' => 1], absolute: false)"
                                >Choose from media</x-ui.button>
                            @endcan

                            @if ($assignment)
                                <details class="admin-row-actions-menu self-end sm:ml-auto sm:self-auto" data-admin-row-actions-menu>
                                    <summary aria-label="More logo actions" aria-haspopup="menu"><span aria-hidden="true">⋮</span></summary>
                                    <div role="menu" data-admin-row-actions-panel>
                                        <button
                                            type="button"
                                            role="menuitem"
                                            class="text-sm font-semibold text-admin-danger"
                                            aria-haspopup="dialog"
                                            aria-controls="remove-brand-logo-modal"
                                            data-admin-modal-open-target="remove-brand-logo-modal"
                                        >Remove logo from brand</button>
                                    </div>
                                </details>
                            @endif
                        </div>
                    @endcan
                </div>

                <aside class="min-w-0 border-t border-admin-border pt-5 lg:border-l lg:border-t-0 lg:pl-admin-card lg:pt-0" data-screen-region="asset-details">
                    <h2 class="text-base font-semibold text-admin-text">Asset details</h2>
                    @if ($asset)
                        <p class="mt-1 text-xs text-admin-muted">Safe metadata for the assigned Shared Media asset.</p>
                        <dl class="mt-4 grid min-w-0 grid-cols-[6rem_minmax(0,1fr)] gap-x-3 gap-y-2 text-sm sm:grid-cols-[8rem_minmax(0,1fr)] lg:grid-cols-[5.5rem_minmax(0,1fr)] xl:grid-cols-[6.5rem_minmax(0,1fr)]">
                            <div class="contents"><dt class="text-xs font-medium text-admin-muted">Filename</dt><dd class="min-w-0 break-all font-medium text-admin-text">{{ $asset->original_filename ?: 'Unnamed media asset' }}</dd></div>
                            <div class="contents"><dt class="text-xs font-medium text-admin-muted">MIME type</dt><dd class="break-all text-admin-text">{{ $asset->mime_type ?: '—' }}</dd></div>
                            <div class="contents"><dt class="text-xs font-medium text-admin-muted">Dimensions</dt><dd class="text-admin-text">{{ $asset->width && $asset->height ? number_format($asset->width).' × '.number_format($asset->height).' px' : '—' }}</dd></div>
                            <div class="contents"><dt class="text-xs font-medium text-admin-muted">File size</dt><dd class="text-admin-text">{{ $formatBytes($asset->file_size) }}</dd></div>
                            <div class="contents"><dt class="text-xs font-medium text-admin-muted">Asset status</dt><dd><x-admin.status-badge :label="ucfirst((string) $asset->status)" :variant="$assetStatusVariant" size="sm" /></dd></div>
                            <div class="contents"><dt class="text-xs font-medium text-admin-muted">Source</dt><dd class="break-all text-admin-text">{{ $asset->source ?: '—' }}</dd></div>
                            <div class="contents"><dt class="text-xs font-medium text-admin-muted">Created</dt><dd class="text-admin-text">{{ $assetCreated ?: '—' }}</dd></div>
                            <div class="contents"><dt class="text-xs font-medium text-admin-muted">Updated</dt><dd class="text-admin-text">{{ $assetUpdated ?: '—' }}</dd></div>
                            <div class="contents"><dt class="text-xs font-medium text-admin-muted">Asset ID</dt><dd class="min-w-0 break-all font-foundation-mono text-xs text-admin-text">{{ $asset->uuid }}</dd></div>
                            <div class="contents"><dt class="text-xs font-medium text-admin-muted">Assignment</dt><dd class="text-admin-text">Brand · Primary logo</dd></div>
                        </dl>
                        @can('media.manage')
                            <div class="mt-4 border-t border-admin-border pt-3">
                                <a href="{{ route('central.media.show', $asset, absolute: false) }}" class="text-sm font-semibold text-admin-primary underline decoration-admin-primary/30 underline-offset-2">Open MediaAsset</a>
                            </div>
                        @endcan
                    @else
                        <div class="mt-4 rounded-admin-input bg-admin-surface-muted px-3 py-4">
                            <p class="text-sm font-semibold text-admin-text">No asset selected</p>
                            <p class="mt-1 text-sm text-admin-muted">Asset details will appear after a logo is assigned.</p>
                        </div>
                    @endif
                </aside>
            </div>
        </x-admin.card>

        <x-admin.card
            title="Generated variants"
            description="Read-only Shared Media outputs for this logo."
            padding="sm"
            data-screen-region="generated-variants"
        >
            @if ($variants === [])
                <div class="flex items-start gap-3 rounded-admin-input bg-admin-surface-muted px-4 py-3">
                    <span class="mt-0.5 text-admin-muted" aria-hidden="true">◇</span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-admin-text">No generated variants yet</p>
                        <p class="mt-1 text-xs text-admin-muted">{{ $asset ? 'Variants will appear once processing is complete. The normalized master can remain usable.' : 'Variants appear after a logo is assigned and processed.' }}</p>
                    </div>
                </div>
            @else
                <div class="grid min-w-0 gap-2 md:grid-cols-3" data-logo-variants>
                    @foreach ($variants as $variant)
                        @php
                            $variantSize = str_replace('brand_logo_', '', $variant->name);
                        @endphp
                        <article class="min-w-0 rounded-admin-input border border-admin-border bg-admin-surface-muted p-3" data-logo-variant="{{ $variant->name }}">
                            <div class="flex min-w-0 items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold text-admin-text">{{ $variantSize }} px logo</h3>
                                    <p class="mt-0.5 break-all font-foundation-mono text-[0.6875rem] text-admin-muted">{{ $variant->name }}</p>
                                </div>
                                <x-admin.status-badge :label="$variant->state->label()" :variant="$variant->state->badgeVariant()" size="sm" />
                            </div>
                            <p class="mt-2 text-xs text-admin-muted">
                                {{ $variant->width && $variant->height ? number_format($variant->width).' × '.number_format($variant->height).' px' : 'Dimensions pending' }}
                                @if ($variant->format) · {{ strtoupper($variant->format) }} @endif
                                @if ($variant->fileSize) · {{ $formatBytes($variant->fileSize) }} @endif
                            </p>
                            @if ($variant->url)
                                <a href="{{ $variant->url }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex text-xs font-semibold text-admin-primary hover:underline">Open variant</a>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </x-admin.card>

        @can('catalog.brands.manage')
            <x-ui.modal id="replace-brand-logo-modal" :title="$asset ? 'Replace logo' : 'Upload logo'" :open="$uploadModalOpen" size="lg">
                <form
                    id="brand-logo-upload-form"
                    class="space-y-admin-card"
                    method="POST"
                    enctype="multipart/form-data"
                    action="{{ route('central.brands.media.logo.store', $brand) }}"
                    data-logo-upload-form
                >
                    @csrf
                    <div>
                        <h3 class="text-sm font-semibold text-admin-text">Upload a new file</h3>
                        <p class="mt-1 text-sm text-admin-muted">The canonical assignment changes only after secure ingest succeeds. The current Shared Media asset is retained.</p>
                    </div>
                    <label for="logo" class="block rounded-admin-card border border-dashed border-admin-border bg-admin-surface-muted p-4 text-sm font-medium text-admin-text">
                        Choose a JPEG, PNG or WebP file
                        <input
                            id="logo"
                            name="logo"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            autofocus
                            class="mt-3 block w-full min-w-0 rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2 text-sm text-admin-text file:mr-3 file:rounded-admin-input file:border-0 file:bg-admin-primary-soft file:px-3 file:py-2 file:font-medium file:text-admin-primary"
                            required
                            aria-describedby="logo-help @error('logo') logo-error @enderror"
                        >
                    </label>
                    <p id="logo-help" class="text-xs text-admin-muted">JPEG, PNG or WebP · maximum 20 MB · maximum 8000 px per side and 16 MP.</p>
                    @error('logo')
                        <p id="logo-error" class="rounded-admin-input border border-admin-danger/30 bg-admin-danger-soft px-3 py-2 text-sm font-medium text-admin-danger" role="alert">{{ $message }}</p>
                    @enderror
                </form>
                <x-slot:footer>
                    <div class="flex flex-wrap justify-end gap-admin-field">
                        <x-ui.button variant="secondary" data-admin-modal-close>Cancel</x-ui.button>
                        <x-ui.button type="submit" form="brand-logo-upload-form">{{ $asset ? 'Replace logo' : 'Upload logo' }}</x-ui.button>
                    </div>
                </x-slot:footer>
            </x-ui.modal>

            @if ($assignment)
                <form id="remove-brand-logo" method="POST" action="{{ route('central.brands.media.logo.destroy', $brand) }}" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
                <x-admin.confirmation-modal
                    id="remove-brand-logo-modal"
                    :title="'Remove logo from '.$brand->name.'?'"
                    message="This removes only the Brand assignment. The Shared Media asset and its files are not deleted and remain available for other uses."
                    confirm-label="Remove logo from brand"
                    confirm-form="remove-brand-logo"
                    variant="danger"
                    :open="false"
                />
            @endif
        @endcan

        @if ($availableAssets !== null)
            <x-ui.modal id="shared-media-picker-modal" title="Choose from Shared Media" :open="$pickerOpen" size="2xl" data-screen-region="shared-media-picker">
                <form method="GET" action="{{ route('central.brands.media', $brand, absolute: false) }}" class="grid min-w-0 gap-admin-field sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                    <input type="hidden" name="picker" value="1">
                    <label class="min-w-0 text-sm font-medium text-admin-text">
                        Search shared media
                        <input
                            type="search"
                            name="asset_search"
                            value="{{ $assetSearch }}"
                            maxlength="255"
                            class="mt-1 w-full min-w-0 rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2"
                            placeholder="Filename, checksum or asset ID"
                            autofocus
                        >
                    </label>
                    <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
                </form>

                @error('media_asset_id')
                    <p class="mt-3 rounded-admin-input border border-admin-danger/30 bg-admin-danger-soft px-3 py-2 text-sm font-medium text-admin-danger" role="alert">{{ $message }}</p>
                @enderror

                @if ($availableAssets->isEmpty())
                    <div class="mt-4 rounded-admin-card border border-dashed border-admin-border bg-admin-surface-muted px-4 py-8 text-center">
                        <p class="text-sm font-semibold text-admin-text">No compatible shared assets</p>
                        <p class="mt-1 text-sm text-admin-muted">Try another filename, checksum or asset ID.</p>
                    </div>
                @else
                    <div class="mt-4 grid min-w-0 grid-cols-1 gap-3 min-[30rem]:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" data-shared-media-results>
                        @foreach ($availableAssets as $candidate)
                            @php
                                $candidateLogo = $availableLogos->get((int) $candidate->getKey());
                                $isCurrent = $asset?->is($candidate) === true;
                            @endphp
                            <article @class([
                                'min-w-0 overflow-hidden rounded-admin-card border bg-admin-surface',
                                'border-admin-primary ring-1 ring-admin-primary/20' => $isCurrent,
                                'border-admin-border' => ! $isCurrent,
                            ]) data-media-asset-card="{{ $candidate->id }}" @if ($isCurrent) aria-current="true" @endif>
                                <div class="relative flex aspect-[16/9] items-center justify-center border-b border-admin-border bg-admin-surface-muted p-3">
                                    @if ($candidateLogo?->url)
                                        <img src="{{ $candidateLogo->url }}" alt="" class="h-full w-full object-contain" loading="lazy">
                                    @else
                                        <span class="text-xs font-medium text-admin-muted">Unavailable preview</span>
                                    @endif
                                    @if ($isCurrent)
                                        <span class="absolute left-2 top-2 rounded-admin-badge bg-admin-primary px-2 py-1 text-[0.6875rem] font-semibold text-white">Current</span>
                                    @endif
                                </div>
                                <div class="min-w-0 p-3">
                                    <h3 class="truncate text-sm font-semibold text-admin-text" title="{{ $candidate->original_filename ?: $candidate->uuid }}">{{ $candidate->original_filename ?: $candidate->uuid }}</h3>
                                    <p class="mt-1 truncate text-xs text-admin-muted">{{ $candidate->mime_type }} · {{ $candidate->width && $candidate->height ? $candidate->width.' × '.$candidate->height.' px' : 'Dimensions unavailable' }}</p>
                                    <form method="POST" action="{{ route('central.brands.media.logo.assign', $brand) }}" class="mt-3">
                                        @csrf
                                        <input type="hidden" name="media_asset_id" value="{{ $candidate->id }}">
                                        <x-ui.button
                                            type="submit"
                                            variant="secondary"
                                            class="w-full"
                                            :disabled="$isCurrent || $candidateLogo?->url === null"
                                        >{{ $isCurrent ? 'Current logo' : 'Use as logo' }}</x-ui.button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="mt-4 min-w-0 overflow-x-auto">{{ $availableAssets->links() }}</div>
                @endif
            </x-ui.modal>
        @endif
    </div>
@endsection
