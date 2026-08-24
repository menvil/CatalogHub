<?php

namespace App\Http\Controllers\CentralAdmin\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\Media\UploadMediaAssetRequest;
use App\Jobs\Media\GenerateMediaVariantsJob;
use App\Models\MediaAsset;
use App\Services\Media\ImageIngestException;
use App\Services\Media\MediaService;
use App\Services\Media\MediaVariantProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class MediaUploadController extends Controller
{
    public function __invoke(UploadMediaAssetRequest $request, MediaService $media): RedirectResponse
    {
        Gate::authorize('create', MediaAsset::class);

        try {
            $asset = $media->uploadOriginal($request->uploadedFile());
        } catch (ImageIngestException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()])->withInput();
        }
        GenerateMediaVariantsJob::dispatch($asset->id, MediaVariantProfile::Default)->afterCommit();

        return redirect()
            ->route('central.media.show', $asset)
            ->with('status', 'Media uploaded.');
    }
}
