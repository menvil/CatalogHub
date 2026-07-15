<?php

namespace App\Http\Controllers\CentralAdmin\Media;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\MediaSource;
use App\Services\Media\MediaUrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class MediaAssetDetailController extends Controller
{
    public function show(MediaAsset $asset, MediaUrlGenerator $urls): View
    {
        Gate::authorize('view', $asset);

        $asset->load(['sources', 'variants']);

        return view('central-admin.media.detail', [
            'asset' => $asset,
            'source' => $asset->sources->first(),
            'urlGenerator' => $urls,
        ]);
    }

    public function updateSource(Request $request, MediaAsset $asset): RedirectResponse
    {
        Gate::authorize('update', $asset);

        $data = $request->validate([
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:2000'],
            'license_type' => ['nullable', 'string', 'max:100'],
            'license_url' => ['nullable', 'url', 'max:2000'],
            'attribution' => ['nullable', 'string', 'max:2000'],
        ]);

        MediaSource::query()->updateOrCreate(
            ['media_asset_id' => $asset->id],
            $data + ['media_asset_id' => $asset->id]
        );

        return redirect()
            ->route('central.media.show', $asset)
            ->with('status', 'Media source saved.');
    }
}
