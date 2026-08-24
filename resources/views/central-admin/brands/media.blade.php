@extends('layouts.central-admin', ['activeNav' => 'brands', 'pageTitle' => 'Brand Media'])

@section('breadcrumbs')
    <a href="{{ route('filament.central.pages.home', absolute: false) }}" class="font-medium hover:text-admin-text">Central Admin</a><span aria-hidden="true">/</span>
    <a href="{{ route('central.brands.index', absolute: false) }}" class="font-medium hover:text-admin-text">Brands</a><span aria-hidden="true">/</span>
    <a href="{{ route('central.brands.show', $brand, absolute: false) }}" class="font-medium hover:text-admin-text">{{ $brand->name }}</a><span aria-hidden="true">/</span><span aria-current="page">Media</span>
@endsection

@section('content')
    <div class="space-y-admin-section">
        <x-admin.page-header screen-id="CA-014" :show-screen-id="false" title="Brand Media" :description="$brand->name.' — manage its primary global logo.'" :breadcrumbs="[]" />
        @include('central-admin.brands.partials.subnav', ['active' => 'media'])
        <x-admin.card title="Brand logo">
            @if ($asset && $logo->url)
                <div class="grid gap-6 md:grid-cols-[minmax(0,18rem)_1fr]">
                    <div class="flex h-52 items-center justify-center rounded-admin-card border border-admin-border bg-[linear-gradient(45deg,#f4f4f5_25%,transparent_25%),linear-gradient(-45deg,#f4f4f5_25%,transparent_25%)] bg-[size:20px_20px] p-6"><img class="max-h-full max-w-full object-contain" src="{{ $logo->url }}" alt="{{ $brand->name }} logo"></div>
                    <div class="space-y-3 text-sm"><p class="font-medium text-admin-text">{{ $asset->original_filename ?? 'Brand logo' }}</p><dl class="grid grid-cols-[7rem_1fr] gap-y-2 text-admin-muted"><dt>MIME</dt><dd>{{ $asset->mime_type }}</dd><dt>Dimensions</dt><dd>{{ $asset->width }} × {{ $asset->height }}</dd><dt>File size</dt><dd>{{ number_format($asset->file_size / 1024, 1) }} KB</dd><dt>Asset</dt><dd class="break-all font-foundation-mono">{{ $asset->uuid }}</dd></dl></div>
                </div>
            @elseif (! $asset)
                <p class="text-sm text-admin-muted">No logo has been assigned to this brand yet.</p>
            @else
                <p class="text-sm text-admin-muted">The assigned logo file is unavailable. You can replace it with a new logo.</p>
            @endif
            <form class="mt-6 space-y-3" method="POST" enctype="multipart/form-data" action="{{ route('central.brands.media.logo.store', $brand) }}">@csrf
                <label class="block text-sm font-medium text-admin-text" for="logo">{{ $asset ? 'Replace logo' : 'Upload logo' }}</label><input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm" required aria-describedby="logo-help logo-error"><p id="logo-help" class="text-sm text-admin-muted">JPEG, PNG or WebP. Maximum 20 MB.</p>@error('logo')<p id="logo-error" class="text-sm text-admin-danger" role="alert">{{ $message }}</p>@enderror
                <x-ui.button type="submit">{{ $asset ? 'Replace logo' : 'Upload logo' }}</x-ui.button>
            </form>
            @if ($asset)<form id="remove-brand-logo" method="POST" action="{{ route('central.brands.media.logo.destroy', $brand) }}" class="mt-4">@csrf @method('DELETE')<x-ui.button variant="danger" aria-haspopup="dialog" aria-controls="remove-brand-logo-modal" data-admin-modal-open-target="remove-brand-logo-modal">Remove logo</x-ui.button></form><x-admin.confirmation-modal id="remove-brand-logo-modal" :title="'Remove this logo from '.$brand->name.'?'" message="The media asset will remain in the Media Library and can still be used elsewhere." confirm-label="Remove logo" confirm-form="remove-brand-logo" variant="danger" :open="false" />@endif
        </x-admin.card>
    </div>
@endsection
