<?php

namespace App\Services\Media;

use App\Models\MediaAsset;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/** Compatibility façade for every HTTP upload caller, including Media Library. */
final readonly class MediaService
{
    public function __construct(private ImageIngestor $ingestor, private MediaStorage $storage, private MediaPathGenerator $paths) {}

    public function uploadOriginal(UploadedFile $file, array $metadata = []): MediaAsset
    {
        $image = $this->ingestor->ingest(ImageInput::fromUploadedFile($file));
        $existing = MediaAsset::query()->where('checksum', $image->checksum)->where('status', 'active')->first();
        if ($existing instanceof MediaAsset) {
            return $existing;
        }

        $disk = (string) ($metadata['disk'] ?? config('media.disk'));
        $uuid = (string) Str::uuid();
        $path = $this->paths->original($uuid, $image->canonicalExtension);
        $this->storage->storeNormalized($disk, $path, $image);
        try {
            $asset = MediaAsset::query()->create([
                'uuid' => $uuid, 'type' => (string) ($metadata['type'] ?? 'image'), 'source' => $metadata['source'] ?? 'manual',
                'disk' => $disk, 'original_path' => $path, 'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $image->mimeType, 'file_size' => $image->byteSize, 'width' => $image->width,
                'height' => $image->height, 'checksum' => $image->checksum, 'status' => 'active',
            ]);
        } catch (QueryException $exception) {
            try {
                $this->storage->delete($disk, $path);
            } catch (\Throwable) { /* preserve DB exception */
            }
            $existing = MediaAsset::query()->where('checksum', $image->checksum)->where('status', 'active')->first();
            if ($existing instanceof MediaAsset) {
                return $existing;
            }
            throw $exception;
        }

        return $asset;
    }
}
