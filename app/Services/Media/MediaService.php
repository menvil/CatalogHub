<?php

namespace App\Services\Media;

use App\Jobs\Media\GenerateMediaVariantsJob;
use App\Models\MediaAsset;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class MediaService
{
    public function uploadOriginal(UploadedFile $file, array $metadata = []): MediaAsset
    {
        $mimeType = (string) $file->getMimeType();

        if (! in_array($mimeType, config('media.allowed_upload_mimes', []), true)) {
            throw new InvalidArgumentException("Unsupported media upload MIME type [{$mimeType}].");
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

        $storedPath = Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));

        if ($storedPath === false) {
            throw new RuntimeException('Unable to store uploaded media original.');
        }

        [$width, $height] = $this->imageDimensions($file);

        try {
            $asset = MediaAsset::query()->create([
                'uuid' => $uuid,
                'type' => (string) ($metadata['type'] ?? 'image'),
                'source' => $metadata['source'] ?? 'manual',
                'disk' => $disk,
                'original_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $mimeType,
                'file_size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'checksum' => $checksum,
                'status' => 'active',
            ]);
        } catch (QueryException $exception) {
            Storage::disk($disk)->delete($path);

            $existing = MediaAsset::query()
                ->where('checksum', $checksum)
                ->where('status', 'active')
                ->first();

            if ($existing instanceof MediaAsset) {
                return $existing;
            }

            throw $exception;
        }

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
