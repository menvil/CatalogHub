<?php

namespace App\Http\Controllers\CentralAdmin\Media;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\MediaSource;
use App\Services\Media\MediaUrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MediaAssetDetailController extends Controller
{
    public function show(Request $request, MediaAsset $asset, MediaUrlGenerator $urls): View
    {
        $this->authorizeMedia($request);

        $asset->load(['sources', 'variants']);

        return view('central-admin.media.detail', [
            'asset' => $asset,
            'source' => $asset->sources->first(),
            'urlGenerator' => $urls,
        ]);
    }

    public function updateSource(Request $request, MediaAsset $asset): RedirectResponse
    {
        $this->authorizeMedia($request);

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

    private function authorizeMedia(Request $request): void
    {
        abort_unless($request->user()?->hasCatalogHubPermission('media.manage'), 403);
    }
}
