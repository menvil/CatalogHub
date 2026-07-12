@extends('layouts.central-admin', ['activeNav' => 'Media', 'pageTitle' => 'Media Asset'])

@section('breadcrumbs')
    <a href="{{ route('central.media.index') }}">Media Library</a>
    <span aria-hidden="true">/</span>
    <span>{{ $asset->original_filename ?? $asset->uuid }}</span>
@endsection

@section('content')
    <div class="grid gap-admin-section lg:grid-cols-[minmax(0,1fr)_420px]">
        <section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card">
            <h2 class="text-lg font-semibold text-admin-text">Asset Preview</h2>
            <div class="mt-4 overflow-hidden rounded-admin-card border border-admin-border bg-admin-surface-muted">
                <img src="{{ $urlGenerator->forAsset($asset) }}" alt="{{ $asset->original_filename ?? 'Media asset' }}" class="max-h-[520px] w-full object-contain">
            </div>
            <dl class="mt-4 grid gap-admin-field text-sm md:grid-cols-2">
                <div><dt class="text-admin-muted">Filename</dt><dd class="font-medium text-admin-text">{{ $asset->original_filename }}</dd></div>
                <div><dt class="text-admin-muted">Checksum</dt><dd class="break-all font-medium text-admin-text">{{ $asset->checksum }}</dd></div>
                <div><dt class="text-admin-muted">Dimensions</dt><dd class="font-medium text-admin-text">{{ $asset->width }}×{{ $asset->height }}</dd></div>
                <div><dt class="text-admin-muted">MIME</dt><dd class="font-medium text-admin-text">{{ $asset->mime_type }}</dd></div>
            </dl>
        </section>

        <section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card">
            <h2 class="text-lg font-semibold text-admin-text">Source & License</h2>

            @if (session('status'))
                <p class="mt-3 rounded-admin-input bg-admin-primary-soft px-3 py-2 text-sm text-admin-primary">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('central.media.source.update', $asset) }}" class="mt-4 space-y-admin-field">
                @csrf
                <label class="block text-sm font-medium text-admin-text">Source type
                    <input name="source_type" value="{{ old('source_type', $source?->source_type) }}" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
                </label>
                <label class="block text-sm font-medium text-admin-text">Source name
                    <input name="source_name" value="{{ old('source_name', $source?->source_name) }}" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
                </label>
                <label class="block text-sm font-medium text-admin-text">Source URL
                    <input name="source_url" value="{{ old('source_url', $source?->source_url) }}" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
                </label>
                <label class="block text-sm font-medium text-admin-text">License type
                    <input name="license_type" value="{{ old('license_type', $source?->license_type) }}" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
                </label>
                <label class="block text-sm font-medium text-admin-text">License URL
                    <input name="license_url" value="{{ old('license_url', $source?->license_url) }}" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
                </label>
                <label class="block text-sm font-medium text-admin-text">Attribution
                    <textarea name="attribution" rows="4" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">{{ old('attribution', $source?->attribution) }}</textarea>
                </label>
                @if ($errors->any())
                    <div class="rounded-admin-input border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif
                <button class="rounded-admin-input bg-admin-primary px-4 py-2 text-sm font-semibold text-white">Save source</button>
            </form>
        </section>
    </div>
@endsection
