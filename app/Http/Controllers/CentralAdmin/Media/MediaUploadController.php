<?php

namespace App\Http\Controllers\CentralAdmin\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\Media\UploadMediaAssetRequest;
use App\Models\MediaAsset;
use App\Services\Media\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class MediaUploadController extends Controller
{
    public function __invoke(UploadMediaAssetRequest $request, MediaService $media): RedirectResponse
    {
        Gate::authorize('create', MediaAsset::class);

        $asset = $media->uploadOriginal($request->uploadedFile());

        return redirect()
            ->route('central.media.show', $asset)
            ->with('status', 'Media uploaded.');
    }
}
