<?php

namespace App\Http\Controllers\CentralAdmin\Media;

use App\Http\Controllers\Controller;
use App\Services\Media\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class MediaUploadController extends Controller
{
    public function __invoke(Request $request, MediaService $media): RedirectResponse
    {
        abort_unless($request->user()?->hasCatalogHubPermission('media.manage'), 403);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:10240'],
        ]);

        $asset = $media->uploadOriginal($data['file']);

        return redirect()
            ->route('central.media.show', $asset)
            ->with('status', 'Media uploaded.');
    }
}
