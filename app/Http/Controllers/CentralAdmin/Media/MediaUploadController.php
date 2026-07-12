<?php

namespace App\Http\Controllers\CentralAdmin\Media;

use App\Http\Controllers\Controller;
use App\Services\Media\MediaService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class MediaUploadController extends Controller
{
    public function __invoke(Request $request, MediaService $media): RedirectResponse
    {
        abort_unless($request->user()?->hasCatalogHubPermission('media.manage'), 403);

        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp',
                'max:10240',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }

                    $dimensions = @getimagesize($value->getRealPath());

                    if ($dimensions === false) {
                        $fail('The uploaded image dimensions could not be read.');

                        return;
                    }

                    [$width, $height] = [(int) $dimensions[0], (int) $dimensions[1]];
                    $pixels = $width * $height;

                    if ($width > (int) config('media.max_upload_width') || $height > (int) config('media.max_upload_height')) {
                        $fail('The uploaded image dimensions are too large.');
                    }

                    if ($pixels > (int) config('media.max_upload_pixels')) {
                        $fail('The uploaded image has too many pixels.');
                    }
                },
            ],
        ]);

        $asset = $media->uploadOriginal($data['file']);

        return redirect()
            ->route('central.media.show', $asset)
            ->with('status', 'Media uploaded.');
    }
}
