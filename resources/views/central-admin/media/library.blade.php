@extends('layouts.central-admin', ['activeNav' => 'Media', 'pageTitle' => 'Media Library'])

@section('breadcrumbs')
    <span>Central</span>
    <span aria-hidden="true">/</span>
    <span>Media</span>
@endsection

@section('content')
    <div class="space-y-admin-section">
        <form method="GET" action="{{ route('central.media.index') }}" class="grid gap-admin-field rounded-admin-card border border-admin-border bg-admin-surface p-admin-card md:grid-cols-4">
            <label class="text-sm font-medium text-admin-text">
                Search
                <input name="search" value="{{ request('search') }}" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2" placeholder="Filename or checksum">
            </label>
            <label class="text-sm font-medium text-admin-text">
                Type
                <select name="type" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
                    <option value="">Any</option>
                    <option value="image" @selected(request('type') === 'image')>Image</option>
                </select>
            </label>
            <label class="text-sm font-medium text-admin-text">
                Status
                <select name="status" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
                    <option value="">Any</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                </select>
            </label>
            <div class="flex items-end">
                <button class="rounded-admin-input bg-admin-primary px-4 py-2 text-sm font-semibold text-white">Filter</button>
            </div>
        </form>

        <form method="POST" action="{{ route('central.media.upload') }}" enctype="multipart/form-data" class="grid gap-admin-field rounded-admin-card border border-admin-border bg-admin-surface p-admin-card md:grid-cols-[minmax(0,1fr)_auto]">
            @csrf
            <label class="text-sm font-medium text-admin-text">
                Upload original image
                <input name="file" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="mt-1 w-full rounded-admin-input border border-admin-border bg-admin-surface px-3 py-2">
            </label>
            <div class="flex items-end">
                <button class="rounded-admin-input bg-admin-primary px-4 py-2 text-sm font-semibold text-white">Upload</button>
            </div>
            @if ($errors->any())
                <div class="rounded-admin-input border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 md:col-span-2">
                    {{ $errors->first() }}
                </div>
            @endif
        </form>

        <section class="rounded-admin-card border border-admin-border bg-admin-surface p-admin-card">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-admin-text">Media Library</h2>
                <p class="text-sm text-admin-muted">{{ $assets->total() }} assets</p>
            </div>

            @if ($assets->isEmpty())
                <div class="mt-4">
                    <x-admin.empty-state title="No media assets" description="Upload media from product media management." icon="+" />
                </div>
            @else
                <div class="mt-4 grid gap-admin-field sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($assets as $asset)
                        @php
                            $thumbnail = $asset->variants->first();
                            $previewUrl = $thumbnail ? $urlGenerator->forVariant($thumbnail) : $urlGenerator->forAsset($asset);
                        @endphp
                        <article class="overflow-hidden rounded-admin-card border border-admin-border bg-admin-surface-muted">
                            <a href="{{ route('central.media.show', $asset) }}" class="block">
                                <div class="flex aspect-square items-center justify-center bg-admin-text text-sm font-semibold text-white">
                                    <img src="{{ $previewUrl }}" alt="{{ $asset->original_filename ?? 'Media asset' }}" loading="lazy" class="h-full w-full object-cover">
                                </div>
                                <div class="space-y-1 p-3">
                                    <h3 class="truncate text-sm font-semibold text-admin-text">{{ $asset->original_filename ?? $asset->uuid }}</h3>
                                    <p class="text-xs text-admin-muted">{{ $asset->type }} · {{ $asset->width }}×{{ $asset->height }}</p>
                                    <p class="truncate text-xs text-admin-muted">{{ $asset->status }} · {{ Str::limit((string) $asset->checksum, 18) }}</p>
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>

                <div class="mt-4">{{ $assets->links() }}</div>
            @endif
        </section>
    </div>
@endsection
