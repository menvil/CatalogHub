<?php

namespace App\Services\Media;

use App\Jobs\Media\GenerateMediaVariantsJob;
use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class MediaService
{
    public function uploadOriginal(UploadedFile $file, array $metadata = []): MediaAsset
    {
        if (! str_starts_with((string) $file->getMimeType(), 'image/')) {
            throw new InvalidArgumentException('Only image uploads are supported by the Phase 8 media engine.');
        }

        $checksum = $this->checksum($file);
        $existing = MediaAsset::query()
            ->where('checksum', $checksum)
            ->where('status', 'active')
            ->first();

        if ($existing instanceof MediaAsset) {
            return $existing;
        }

        $disk = (string) ($metadata['disk'] ?? config('media.disk', 'public'));
        $uuid = (string) Str::uuid();
        $extension = strtolower($file->extension() ?: $file->guessExtension() ?: 'jpg');
        $path = sprintf('media/originals/%s/%s/%s.%s', substr($uuid, 0, 2), substr($uuid, 2, 2), $uuid, $extension);

        Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));

        [$width, $height] = $this->imageDimensions($file);

        $asset = MediaAsset::query()->create([
            'uuid' => $uuid,
            'type' => (string) ($metadata['type'] ?? 'image'),
            'source' => $metadata['source'] ?? 'manual',
            'disk' => $disk,
            'original_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'checksum' => $checksum,
            'status' => 'active',
        ]);

        if ((bool) config('media.dispatch_variants_on_upload', false)) {
            GenerateMediaVariantsJob::dispatch($asset->id);
        }

        return $asset;
    }

    private function checksum(UploadedFile $file): string
    {
        return 'sha256:'.hash_file('sha256', $file->getRealPath());
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function imageDimensions(UploadedFile $file): array
    {
        $dimensions = @getimagesize($file->getRealPath());

        if ($dimensions === false) {
            return [null, null];
        }

        return [(int) $dimensions[0], (int) $dimensions[1]];
    }
}
