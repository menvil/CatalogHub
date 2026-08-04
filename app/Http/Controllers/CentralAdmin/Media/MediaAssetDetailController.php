<?php

namespace App\Http\Controllers\CentralAdmin\Media;

use App\Actions\Media\UpdateMediaSourceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\Media\UpdateMediaSourceRequest;
use App\Models\MediaAsset;
use App\Queries\Media\MediaAssetDetailQuery;
use App\Services\Media\MediaUrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class MediaAssetDetailController extends Controller
{
    public function show(MediaAsset $asset, MediaUrlGenerator $urls, MediaAssetDetailQuery $assets): View
    {
        Gate::authorize('view', $asset);

        $asset = $assets->get($asset);

        return view('central-admin.media.detail', [
            'asset' => $asset,
            'source' => $asset->sources->first(),
            'urlGenerator' => $urls,
        ]);
    }

    public function updateSource(
        UpdateMediaSourceRequest $request,
        MediaAsset $asset,
        UpdateMediaSourceAction $action,
    ): RedirectResponse {
        Gate::authorize('update', $asset);

        $action->handle($asset, $request->payload());

        return redirect()
            ->route('central.media.show', $asset)
            ->with('status', 'Media source saved.');
    }
}
