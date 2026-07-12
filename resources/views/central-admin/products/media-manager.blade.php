@extends('layouts.central-admin', ['activeNav' => 'Products', 'pageTitle' => 'Product Media'])

@section('breadcrumbs')
    <span>Products</span>
    <span aria-hidden="true">/</span>
    <span>{{ $product->name }}</span>
    <span aria-hidden="true">/</span>
    <span>Media</span>
@endsection

@section('content')
    <div class="space-y-admin-section">
        @if (session('status'))
            <p class="rounded-admin-input bg-admin-primary-soft px-3 py-2 text-sm text-admin-primary">{{ session('status') }}</p>
        @endif

        <section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card">
            <h2 class="text-lg font-semibold text-admin-text">Product Media</h2>
            <p class="mt-1 text-sm text-admin-muted">{{ $product->name }}</p>

            <div class="mt-4 grid gap-admin-field md:grid-cols-2 xl:grid-cols-3">
                @foreach ($roles as $role)
                    <article class="rounded-admin-card border border-admin-border bg-admin-surface-muted p-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-admin-muted">{{ $role }}</h3>
                        <div class="mt-3 space-y-3">
                            @forelse (($assignments[$role] ?? collect()) as $assignment)
                                @php
                                    $asset = $assignment->asset;
                                    $variant = $asset->variants->firstWhere('variant_type', 'thumbnail');
                                    $previewUrl = $variant ? $urlGenerator->forVariant($variant) : $urlGenerator->forAsset($asset);
                                @endphp
                                <div class="flex gap-3 rounded-admin-input border border-admin-border bg-admin-surface p-2">
                                    <img src="{{ $previewUrl }}" alt="{{ $asset->original_filename ?? $role }}" class="h-16 w-16 rounded-admin-input object-cover">
                                    <div class="min-w-0 text-sm">
                                        <p class="truncate font-medium text-admin-text">{{ $asset->original_filename ?? $asset->uuid }}</p>
                                        <p class="text-admin-muted">position {{ $assignment->position }}</p>
                                        <p class="text-admin-muted">{{ $assignment->locale ?? 'global' }} · site {{ $assignment->site_id ?? '-' }} · market {{ $assignment->market_id ?? '-' }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-admin-muted">No {{ $role }} media assigned.</p>
                            @endforelse
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="grid gap-admin-section lg:grid-cols-2">
            <div class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card">
                <h2 class="text-lg font-semibold text-admin-text">Assign Existing Media</h2>
                <form method="POST" action="{{ route('central.products.media.assign', $product) }}" class="mt-4 space-y-admin-field">
                    @csrf
                    <label class="block text-sm font-medium text-admin-text">Asset
                        <select name="media_asset_id" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
                            @foreach ($assets as $asset)
                                <option value="{{ $asset->id }}">{{ $asset->original_filename ?? $asset->uuid }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-medium text-admin-text">Role
                        <select name="role" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
                            @foreach ($roles as $role)
                                <option value="{{ $role }}">{{ $role }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="grid gap-admin-field md:grid-cols-3">
                        <label class="block text-sm font-medium text-admin-text">Locale
                            <input name="locale" placeholder="de-DE" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
                        </label>
                        <label class="block text-sm font-medium text-admin-text">Site ID
                            <input name="site_id" type="number" min="1" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
                        </label>
                        <label class="block text-sm font-medium text-admin-text">Market ID
                            <input name="market_id" type="number" min="1" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
                        </label>
                    </div>
                    @if ($errors->any())
                        <div class="rounded-admin-input border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</div>
                    @endif
                    <button class="rounded-admin-input bg-admin-primary px-4 py-2 text-sm font-semibold text-white">Assign media</button>
                </form>
            </div>

            <div class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card">
                <h2 class="text-lg font-semibold text-admin-text">Fallback Preview</h2>
                <form method="GET" action="{{ route('central.products.media', $product) }}" class="mt-4 grid gap-admin-field md:grid-cols-2">
                    <label class="block text-sm font-medium text-admin-text">Role
                        <select name="preview_role" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected($previewRole === $role)>{{ $role }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-medium text-admin-text">Locale
                        <input name="preview_locale" value="{{ $previewLocale }}" placeholder="de-DE" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
                    </label>
                    <label class="block text-sm font-medium text-admin-text">Site ID
                        <input name="preview_site_id" value="{{ $previewSiteId }}" type="number" min="1" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
                    </label>
                    <label class="block text-sm font-medium text-admin-text">Market ID
                        <input name="preview_market_id" value="{{ $previewMarketId }}" type="number" min="1" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
                    </label>
                    <div class="md:col-span-2">
                        <button class="rounded-admin-input border border-admin-border bg-admin-surface px-4 py-2 text-sm font-semibold text-admin-text">Refresh fallback preview</button>
                    </div>
                </form>

                <div class="mt-4 rounded-admin-card border border-admin-border bg-admin-surface-muted p-4">
                    <h3 class="text-sm font-semibold text-admin-text">Resolved media</h3>
                    @if ($resolution->found())
                        <p class="mt-2 text-sm text-admin-muted">{{ $resolution->asset->original_filename ?? $resolution->asset->uuid }}</p>
                        <p class="text-sm text-admin-muted">Matched: {{ $resolution->matchedStep }}</p>
                    @else
                        <p class="mt-2 text-sm text-admin-muted">Placeholder fallback</p>
                    @endif
                    <ol class="mt-3 list-decimal space-y-1 pl-5 text-sm text-admin-muted">
                        @foreach ($resolution->fallbackChain as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </section>
    </div>
@endsection
