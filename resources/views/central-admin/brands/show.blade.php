@extends('layouts.central-admin', ['activeNav' => 'brands', 'pageTitle' => $brand->name])

@php
    $statusVariant = $brand->status->color() === 'gray' ? 'neutral' : $brand->status->color();
    $websiteIsSafe = \App\Support\Presentation\SafePresentationUrl::allows($brand->website_url);
    $supportUrlIsSafe = \App\Support\Presentation\SafePresentationUrl::allows($brand->support_url);
    $primaryColorIsSafe = is_string($brand->primary_color)
        && preg_match('/\A#[0-9A-F]{6}\z/', $brand->primary_color) === 1;
    $productsCount = (int) $brand->products_count;
    $lifecycleError = $errors->first('status') ?: session('lifecycle_error');
@endphp

@section('breadcrumbs')
    <a href="{{ route('filament.central.pages.home', absolute: false) }}" class="font-medium hover:text-admin-text">Central Admin</a>
    <span aria-hidden="true">/</span>
    <a href="{{ route('central.brands.index', absolute: false) }}" class="font-medium hover:text-admin-text">Brands</a>
    <span aria-hidden="true">/</span>
    <span aria-current="page">{{ $brand->name }}</span>
@endsection

@section('content')
    <div class="space-y-admin-section" data-brand-detail-fixture="brand-detail-v2">
        <x-admin.page-header
            screen-id="CA-012"
            :show-screen-id="false"
            :title="$brand->name"
            description="Canonical brand in the central catalog."
            :breadcrumbs="[]"
        >
            <x-slot:actions>
                @if ($logo->url)
                    <img src="{{ $logo->url }}" alt="{{ $brand->name }} logo" class="h-10 w-16 rounded border border-admin-border object-contain p-1">
                @endif
                <div data-screen-region="status-context">
                    <x-admin.status-badge :label="$brand->status->label()" :variant="$statusVariant" />
                </div>
                @can('catalog.brands.manage')
                    <x-ui.button variant="secondary" :href="route('central.brands.edit', $brand, absolute: false)">Edit Brand</x-ui.button>
                @endcan
            </x-slot:actions>
        </x-admin.page-header>

        @include('central-admin.brands.partials.subnav', ['active' => 'overview'])

        <x-admin.detail-layout>
            <x-slot:main>
                <x-admin.card title="General information" data-screen-region="general-information">
                    <dl class="divide-y divide-admin-border">
                        <div class="grid gap-1 py-3 first:pt-0 sm:grid-cols-[10rem_minmax(0,1fr)]">
                            <dt class="text-sm font-medium text-admin-muted">Name</dt>
                            <dd class="min-w-0 break-words text-sm text-admin-text">{{ $brand->name }}</dd>
                        </div>
                        <div class="grid gap-1 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]">
                            <dt class="text-sm font-medium text-admin-muted">Slug</dt>
                            <dd class="min-w-0 break-all font-foundation-mono text-sm text-admin-text">{{ $brand->slug }}</dd>
                        </div>
                        <div class="grid gap-1 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]">
                            <dt class="text-sm font-medium text-admin-muted">Status</dt>
                            <dd class="text-sm text-admin-text">{{ $brand->status->label() }}</dd>
                        </div>
                        <div class="grid gap-1 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]">
                            <dt class="text-sm font-medium text-admin-muted">Country</dt>
                            <dd class="min-w-0 break-words text-sm text-admin-text">
                                {{ $countryName === null ? '—' : $countryName.' ('.$brand->country->alpha2.')' }}
                            </dd>
                        </div>
                        <div class="grid gap-1 pt-3 sm:grid-cols-[10rem_minmax(0,1fr)]">
                            <dt class="text-sm font-medium text-admin-muted">Founded</dt>
                            <dd class="text-sm text-admin-text">{{ $brand->founded_year ?? '—' }}</dd>
                        </div>
                    </dl>
                </x-admin.card>

                <x-admin.card title="Online presence" data-screen-region="online-presence">
                    <dl class="divide-y divide-admin-border">
                        <div class="grid gap-1 py-3 first:pt-0 sm:grid-cols-[10rem_minmax(0,1fr)]">
                            <dt class="text-sm font-medium text-admin-muted">Website</dt>
                            <dd class="min-w-0 break-all text-sm text-admin-text">
                                @if ($brand->website_url === null)
                                    —
                                @elseif ($websiteIsSafe)
                                    <a href="{{ $brand->website_url }}" target="_blank" rel="noopener noreferrer" class="font-medium text-admin-primary underline decoration-admin-primary/30 underline-offset-2">{{ $brand->website_url }}</a>
                                @else
                                    {{ $brand->website_url }}
                                @endif
                            </dd>
                        </div>
                        <div class="grid gap-1 py-3 sm:grid-cols-[10rem_minmax(0,1fr)]">
                            <dt class="text-sm font-medium text-admin-muted">Support URL</dt>
                            <dd class="min-w-0 break-all text-sm text-admin-text">
                                @if ($brand->support_url === null)
                                    —
                                @elseif ($supportUrlIsSafe)
                                    <a href="{{ $brand->support_url }}" target="_blank" rel="noopener noreferrer" class="font-medium text-admin-primary underline decoration-admin-primary/30 underline-offset-2">{{ $brand->support_url }}</a>
                                @else
                                    {{ $brand->support_url }}
                                @endif
                            </dd>
                        </div>
                        <div class="grid gap-1 pt-3 sm:grid-cols-[10rem_minmax(0,1fr)]">
                            <dt class="text-sm font-medium text-admin-muted">Contact email</dt>
                            <dd class="min-w-0 break-all text-sm text-admin-text">{{ $brand->contact_email ?? '—' }}</dd>
                        </div>
                    </dl>
                </x-admin.card>

                <x-admin.card title="Brand identity" data-screen-region="brand-identity">
                    <div class="flex items-center justify-between gap-admin-field">
                        <span class="text-sm font-medium text-admin-muted">Primary color</span>
                        @if ($brand->primary_color === null)
                            <span class="text-sm text-admin-text">—</span>
                        @else
                            <span class="flex items-center gap-2 font-foundation-mono text-sm text-admin-text">
                                @if ($primaryColorIsSafe)
                                    <span class="h-6 w-6 rounded-admin-input border border-admin-border" style="background-color: {{ $brand->primary_color }}" aria-hidden="true"></span>
                                @endif
                                {{ $brand->primary_color }}
                            </span>
                        @endif
                    </div>
                </x-admin.card>

                <x-admin.card title="Usage" data-screen-region="usage">
                    <div class="flex items-baseline justify-between gap-admin-field">
                        <span class="text-sm font-medium text-admin-muted">Products</span>
                        <strong class="text-2xl font-semibold text-admin-text" data-products-count="{{ $productsCount }}">{{ number_format($productsCount) }}</strong>
                    </div>
                    <p class="mt-2 text-sm text-admin-muted">
                        @if ($productsCount === 0)
                            No canonical products reference this brand yet.
                        @elseif ($productsCount === 1)
                            1 canonical product references this brand.
                        @else
                            {{ number_format($productsCount) }} canonical products reference this brand.
                        @endif
                    </p>
                </x-admin.card>
            </x-slot:main>

            <x-slot:aside>
                <x-admin.card title="Record" data-screen-region="record-metadata">
                    <dl class="space-y-admin-card">
                        <div class="flex items-start justify-between gap-admin-field">
                            <dt class="text-sm font-medium text-admin-muted">Status</dt>
                            <dd><x-admin.status-badge :label="$brand->status->label()" :variant="$statusVariant" size="sm" /></dd>
                        </div>
                        <div class="flex flex-col gap-1">
                            <dt class="text-sm font-medium text-admin-muted">Created</dt>
                            <dd><x-ui.timestamp :value="$brand->created_at" timezone="UTC" /></dd>
                        </div>
                        <div class="flex flex-col gap-1">
                            <dt class="text-sm font-medium text-admin-muted">Updated</dt>
                            <dd><x-ui.timestamp :value="$brand->updated_at" timezone="UTC" /></dd>
                        </div>
                        <div class="flex items-start justify-between gap-admin-field">
                            <dt class="text-sm font-medium text-admin-muted">Record ID</dt>
                            <dd class="break-all font-foundation-mono text-sm text-admin-text">{{ $brand->getKey() }}</dd>
                        </div>
                    </dl>
                </x-admin.card>

                <x-admin.card title="Lifecycle" data-screen-region="lifecycle">
                    @if ($lifecycleError)
                        <p class="mb-admin-card rounded-admin-input border border-admin-danger/30 bg-admin-danger-soft px-3 py-2 text-sm text-admin-text" role="alert" data-lifecycle-error>
                            {{ $lifecycleError }}
                        </p>
                    @endif

                    @can('catalog.brands.manage')
                    @switch($brand->status)
                        @case(\App\Enums\CentralBrandStatus::Draft)
                            <p class="text-sm text-admin-muted">Draft brands are not yet ready for normal catalog use.</p>
                            <div class="mt-admin-card grid gap-admin-field">
                                <x-ui.button class="w-full" aria-haspopup="dialog" aria-controls="activate-brand-modal" data-admin-modal-open-target="activate-brand-modal">Activate Brand</x-ui.button>
                                <x-ui.button variant="danger" class="w-full" aria-haspopup="dialog" aria-controls="archive-brand-modal" data-admin-modal-open-target="archive-brand-modal">Archive Brand</x-ui.button>
                            </div>
                            @break

                        @case(\App\Enums\CentralBrandStatus::Active)
                            <p class="text-sm text-admin-muted">Active brands are available for normal catalog use.</p>
                            <div class="mt-admin-card">
                                <x-ui.button variant="danger" class="w-full" aria-haspopup="dialog" aria-controls="archive-brand-modal" data-admin-modal-open-target="archive-brand-modal">Archive Brand</x-ui.button>
                            </div>
                            @break

                        @case(\App\Enums\CentralBrandStatus::Archived)
                            <p class="text-sm text-admin-muted">Archived brands remain in existing references but should not be used for new relationships.</p>
                            <p class="mt-2 text-sm text-admin-muted">Restoring returns the brand to Draft. It must be activated separately.</p>
                            <div class="mt-admin-card">
                                <x-ui.button class="w-full" aria-haspopup="dialog" aria-controls="restore-brand-modal" data-admin-modal-open-target="restore-brand-modal">Restore Brand</x-ui.button>
                            </div>
                            @break
                    @endswitch
                    @endcan
                </x-admin.card>
            </x-slot:aside>
        </x-admin.detail-layout>

        @can('catalog.brands.manage')
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
