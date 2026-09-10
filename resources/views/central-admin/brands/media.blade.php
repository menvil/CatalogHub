@extends('layouts.central-admin', ['activeNav' => 'brands', 'pageTitle' => 'Brand Media'])

@section('breadcrumbs')
    <a href="{{ route('filament.central.pages.home', absolute: false) }}" class="font-medium hover:text-admin-text">Central Admin</a><span aria-hidden="true">/</span>
    <a href="{{ route('central.brands.index', absolute: false) }}" class="font-medium hover:text-admin-text">Brands</a><span aria-hidden="true">/</span>
    <a href="{{ route('central.brands.show', $brand, absolute: false) }}" class="font-medium hover:text-admin-text">{{ $brand->name }}</a><span aria-hidden="true">/&nbsp;</span><span aria-current="page">Media</span>
@endsection

@section('content')
    @php
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
        $assetStatusVariant = match ((string) $asset?->status) {
            'active' => 'success',
            'pending', 'processing' => 'warning',
            'failed' => 'danger',
            default => 'neutral',
        };
    @endphp

    <div class="min-w-0 space-y-admin-section" data-brand-media-fixture="brand-media-v6">
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

        <div class="grid min-w-0 items-stretch gap-admin-section xl:grid-cols-[minmax(0,1.8fr)_minmax(18rem,1fr)]" data-screen-region="brand-logo-workspace">
                <x-admin.card
                    class="xl:col-start-1 xl:row-start-1"
                    title="Primary logo"
                    description="The canonical global logo used across the central catalog."
                    data-screen-region="primary-logo"
                    data-brand-media-role="brand_logo"
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

                    <div class="grid min-w-0 items-start gap-admin-card md:grid-cols-3">
                        <div @class([
                            'min-w-0',
                            'md:col-span-3' => \Illuminate\Support\Facades\Gate::denies('catalog.brands.manage'),
                        ])>
                    @if ($logo->state === \App\Enums\MediaDeliveryState::Ready && $logo->url !== null)
                        <div class="flex h-48 items-center justify-center overflow-hidden rounded-admin-card border border-admin-border bg-admin-surface-muted p-5 sm:h-56 lg:h-64 lg:p-8" data-logo-preview>
                            <img class="h-full w-full object-contain" src="{{ $logo->url }}" alt="{{ $brand->name }} logo">
                        </div>
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

                        </div>

                    @can('catalog.brands.manage')
                        <form
                            id="brand-logo-upload-form"
                            method="POST"
                            enctype="multipart/form-data"
                            action="{{ route('central.brands.media.logo.store', $brand) }}"
                            @class([
                                'flex min-w-0 flex-col justify-center gap-admin-field rounded-admin-card border border-admin-border bg-admin-surface-muted p-4 md:col-span-2',
                                'sm:h-56 lg:h-64' => ! $errors->has('logo'),
                                'sm:min-h-56 lg:min-h-64' => $errors->has('logo'),
                            ])
                            data-logo-upload-form
                        >
                            @csrf

                            <div>
                                <h3 class="text-sm font-semibold text-admin-text">Upload a new file</h3>
                                <p class="mt-1 text-sm text-admin-muted">The canonical assignment changes only after secure ingest succeeds. The current Shared Media asset is retained.</p>
                            </div>

                            <label for="logo" class="inline-flex min-h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-admin-input border border-admin-primary bg-admin-primary px-4 py-3 text-base font-semibold text-white transition hover:brightness-95 focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-admin-primary">
                                <x-ui.icon name="arrow-up-tray" decorative class="h-5 w-5" />
                                <span>Upload Photo</span>
                                <input
                                    id="logo"
                                    name="logo"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="sr-only"
                                    required
                                    aria-describedby="logo-help @error('logo') logo-error @enderror"
                                    onchange="if (this.files.length > 0) this.form.requestSubmit()"
                                >
                            </label>

                            <p id="logo-help" class="text-center text-xs leading-5 text-admin-muted">JPEG, PNG or WebP · max 20 MB<br>max 8000 px per side · max 16 MP</p>

                            @error('logo')
                                <p id="logo-error" class="rounded-admin-input border border-admin-danger/30 bg-admin-danger-soft px-3 py-2 text-sm font-medium text-admin-danger" role="alert">{{ $message }}</p>
                            @enderror
                        </form>
                    @endcan

                        @if ($logo->state === \App\Enums\MediaDeliveryState::Ready && $logo->url !== null || $assignment)
                            <div class="flex min-w-0 items-center justify-between gap-4 md:col-span-3">
                                @if ($logo->state === \App\Enums\MediaDeliveryState::Ready && $logo->url !== null)
                                    <p class="min-w-0 text-xs text-admin-muted">Displaying the normalized master.</p>
                                @else
                                    <span aria-hidden="true"></span>
                                @endif

                                @can('catalog.brands.manage')
                                    @if ($assignment)
                                        <button
                                            type="button"
                                            class="shrink-0 text-xs font-semibold text-admin-danger underline decoration-admin-danger/30 underline-offset-2 transition hover:decoration-admin-danger focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-admin-primary"
                                            aria-haspopup="dialog"
                                            aria-controls="remove-brand-logo-modal"
                                            data-admin-modal-open-target="remove-brand-logo-modal"
                                        >Remove logo from brand</button>
                                    @endif
                                @endcan
                            </div>
                        @endif
                    </div>

                    <section class="mt-admin-card border-t border-admin-border pt-admin-card" data-screen-region="generated-variants">
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-admin-text">Generated variants</h3>
                            <p class="mt-1 text-sm text-admin-muted">Read-only Shared Media outputs for this logo.</p>
                        </div>

                        @if ($variants === [])
                            <div class="mt-admin-card flex items-start gap-3 rounded-admin-input bg-admin-surface-muted px-4 py-3">
                                <span class="mt-0.5 text-admin-muted" aria-hidden="true">◇</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-admin-text">No generated variants yet</p>
                                    <p class="mt-1 text-xs text-admin-muted">{{ $asset ? 'Variants will appear once processing is complete. The normalized master can remain usable.' : 'Variants appear after a logo is assigned and processed.' }}</p>
                                </div>
                            </div>
                        @else
                            <div class="mt-admin-card grid min-w-0 gap-2 md:grid-cols-3" data-logo-variants>
                                @foreach ($variants as $variant)
                                    @php
                                        $variantSize = str_replace('brand_logo_', '', $variant->name);
                                    @endphp
                                    <article class="min-w-0 rounded-admin-input border border-admin-border bg-admin-surface-muted p-3" data-logo-variant="{{ $variant->name }}">
                                        <div class="flex min-w-0 items-stretch justify-between gap-2">
                                            <div class="flex min-w-0 flex-1 flex-col">
                                                <h4 class="text-sm font-semibold text-admin-text">{{ $variantSize }} px logo</h4>
                                                <p class="mt-0.5 break-all font-foundation-mono text-[0.6875rem] text-admin-muted">{{ $variant->name }}</p>
                                                <p class="mt-2 text-xs text-admin-muted">
                                                    {{ $variant->width && $variant->height ? number_format($variant->width).' × '.number_format($variant->height).' px' : 'Dimensions pending' }}
                                                    @if ($variant->format) · {{ strtoupper($variant->format) }} @endif
                                                    @if ($variant->fileSize) · {{ $formatBytes($variant->fileSize) }} @endif
                                                </p>
                                                @if ($variant->url)
                                                    <a href="{{ $variant->url }}" target="_blank" rel="noopener noreferrer" class="mt-auto inline-flex pt-2 text-xs font-semibold text-admin-primary hover:underline">Open variant</a>
                                                @endif
                                            </div>

                                            <div class="flex w-16 shrink-0 self-stretch flex-col items-end justify-between gap-1.5">
                                                <x-admin.status-badge :label="$variant->state->label()" :variant="$variant->state->badgeVariant()" size="sm" />
                                                @if ($variant->url)
                                                    <div class="flex h-14 w-16 items-center justify-center overflow-hidden rounded-admin-input border border-admin-border bg-admin-surface p-1.5">
                                                        <img src="{{ $variant->url }}" alt="" class="h-full w-full object-contain">
                                                    </div>
                                                @else
                                                    <div class="flex h-14 w-16 items-center justify-center rounded-admin-input border border-admin-border bg-admin-surface p-1.5">
                                                        <span class="text-[0.625rem] font-medium text-admin-muted">No preview</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>
                </x-admin.card>

            <x-admin.card
                class="h-full xl:col-start-2 xl:row-start-1"
                title="Asset details"
                data-screen-region="asset-details"
            >
                @if ($asset)
                    <p class="text-xs text-admin-muted">Safe metadata for the assigned Shared Media asset.</p>
                    <table class="mt-4 w-full table-fixed text-left text-sm">
                        <tbody>
                            <tr class="align-top"><th scope="row" class="w-32 py-2 pr-4 font-normal text-admin-muted">Filename</th><td class="min-w-0 break-all py-2 font-medium text-admin-text">{{ $asset->original_filename ?: 'Unnamed media asset' }}</td></tr>
                            <tr class="align-top"><th scope="row" class="w-32 py-2 pr-4 font-normal text-admin-muted">MIME type</th><td class="break-all py-2 text-admin-text">{{ $asset->mime_type ?: '—' }}</td></tr>
                            <tr class="align-top"><th scope="row" class="w-32 py-2 pr-4 font-normal text-admin-muted">Dimensions</th><td class="py-2 text-admin-text">{{ $asset->width && $asset->height ? number_format($asset->width).' × '.number_format($asset->height).' px' : '—' }}</td></tr>
                            <tr class="align-top"><th scope="row" class="w-32 py-2 pr-4 font-normal text-admin-muted">File size</th><td class="py-2 text-admin-text">{{ $formatBytes($asset->file_size) }}</td></tr>
                            <tr class="align-top"><th scope="row" class="w-32 py-2 pr-4 font-normal text-admin-muted">Asset status</th><td class="py-2"><x-admin.status-badge :label="ucfirst((string) $asset->status)" :variant="$assetStatusVariant" size="sm" /></td></tr>
                            <tr class="align-top"><th scope="row" class="w-32 py-2 pr-4 font-normal text-admin-muted">Source</th><td class="break-all py-2 text-admin-text">{{ $asset->source ?: '—' }}</td></tr>
                            <tr class="align-top"><th scope="row" class="w-32 py-2 pr-4 font-normal text-admin-muted">Created</th><td class="py-2 text-admin-text">{{ $assetCreated ?: '—' }}</td></tr>
                            <tr class="align-top"><th scope="row" class="w-32 py-2 pr-4 font-normal text-admin-muted">Updated</th><td class="py-2 text-admin-text">{{ $assetUpdated ?: '—' }}</td></tr>
                            <tr class="align-top"><th scope="row" class="w-32 py-2 pr-4 font-normal text-admin-muted">Asset ID</th><td class="min-w-0 break-all py-2 font-foundation-mono text-xs text-admin-text">{{ $asset->uuid }}</td></tr>
                            <tr class="align-top"><th scope="row" class="w-32 py-2 pr-4 font-normal text-admin-muted">Assignment</th><td class="py-2 text-admin-text">Brand · Primary logo</td></tr>
                        </tbody>
                    </table>
                    @can('media.manage')
                        <div class="mt-5 border-t border-admin-border pt-4">
                            <x-ui.button variant="secondary" :href="route('central.media.show', $asset, absolute: false)">Open MediaAsset</x-ui.button>
                        </div>
                    @endcan
                @else
                    <div class="rounded-admin-input bg-admin-surface-muted px-3 py-4">
                        <p class="text-sm font-semibold text-admin-text">No asset selected</p>
                        <p class="mt-1 text-sm text-admin-muted">Asset details will appear after a logo is assigned.</p>
                    </div>
                @endif
            </x-admin.card>

        </div>

        @can('media.manage')
            @include('central-admin.brands.partials.media-picker')
        @endcan

        @can('catalog.brands.manage')
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

    </div>
@endsection
