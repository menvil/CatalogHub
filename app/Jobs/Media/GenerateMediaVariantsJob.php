<?php

namespace App\Jobs\Media;

use App\Models\MediaAsset;
use App\Models\MediaVariant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class GenerateMediaVariantsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 1;

    public function __construct(public int $mediaAssetId) {}

    public function handle(): void
    {
        $asset = MediaAsset::query()->findOrFail($this->mediaAssetId);
        $variants = config('media.variants', []);

        foreach ($variants as $type => $config) {
            $this->generate($asset, (string) $type, $config);
        }
    }

    /**
     * @param  array{width: int, height: int, fit: string, format: string, quality: int}  $config
     */
    private function generate(MediaAsset $asset, string $type, array $config): void
    {
        $disk = Storage::disk($asset->disk);

        if (! $disk->exists($asset->original_path)) {
            $this->markFailed($asset, $type, $config);

            return;
        }

        try {
            [$image, $sourceWidth, $sourceHeight] = $this->loadImage((string) $disk->get($asset->original_path), (string) $asset->mime_type);
            [$target, $width, $height] = $this->resize($image, $sourceWidth, $sourceHeight, $config);

            $format = strtolower((string) $config['format']);
            $path = sprintf(
                'media/variants/%s/%s.%s',
                $asset->uuid,
                $type,
                $format === 'jpg' ? 'jpg' : $format
            );

            $contents = $this->encodeImage($target, $format, (int) $config['quality']);

            if ($contents === '' || $disk->put($path, $contents) === false) {
                throw new RuntimeException('Unable to store generated media variant.');
            }

            MediaVariant::query()->updateOrCreate(
                [
                    'media_asset_id' => $asset->id,
                    'variant_type' => $type,
                    'locale' => null,
                    'site_id' => null,
                    'market_id' => null,
                ],
                [
                    'disk' => $asset->disk,
                    'path' => $path,
                    'width' => $width,
                    'height' => $height,
                    'format' => $format,
                    'file_size' => $disk->size($path),
                    'quality' => (int) $config['quality'],
                    'transform_hash' => hash('sha256', json_encode($config, JSON_THROW_ON_ERROR)),
                    'status' => 'ready',
                ]
            );

            imagedestroy($image);
            imagedestroy($target);
        } catch (\Throwable) {
            $this->markFailed($asset, $type, $config);
        }
    }

    /**
     * @return array{0: \GdImage, 1: int, 2: int}
     */
    private function loadImage(string $contents, string $mime): array
    {
        if (! in_array($mime, config('media.allowed_upload_mimes', []), true)) {
            throw new RuntimeException("Unsupported image mime [{$mime}].");
        }

        $image = imagecreatefromstring($contents);

        if (! $image instanceof \GdImage) {
            throw new RuntimeException("Unsupported image mime [{$mime}].");
        }

        return [$image, imagesx($image), imagesy($image)];
    }

    /**
     * @param  array{width: int, height: int, fit: string, format: string, quality: int}  $config
     * @return array{0: \GdImage, 1: int, 2: int}
     */
    private function resize(\GdImage $image, int $sourceWidth, int $sourceHeight, array $config): array
    {
        $targetWidth = (int) $config['width'];
        $targetHeight = (int) $config['height'];

        if ($config['fit'] === 'cover') {
            $scale = max($targetWidth / $sourceWidth, $targetHeight / $sourceHeight);
            $resizedWidth = (int) ceil($sourceWidth * $scale);
            $resizedHeight = (int) ceil($sourceHeight * $scale);
            $offsetX = (int) floor(($targetWidth - $resizedWidth) / 2);
            $offsetY = (int) floor(($targetHeight - $resizedHeight) / 2);
            $target = imagecreatetruecolor($targetWidth, $targetHeight);
            if (! $target instanceof \GdImage) {
                throw new RuntimeException('Unable to allocate target image.');
            }
            imagealphablending($target, false);
            imagesavealpha($target, true);
            if (! imagecopyresampled($target, $image, $offsetX, $offsetY, 0, 0, $resizedWidth, $resizedHeight, $sourceWidth, $sourceHeight)) {
                throw new RuntimeException('Unable to resize source image.');
            }

            return [$target, $targetWidth, $targetHeight];
        }

        $scale = min(1, $targetWidth / $sourceWidth, $targetHeight / $sourceHeight);
        $width = max(1, (int) floor($sourceWidth * $scale));
        $height = max(1, (int) floor($sourceHeight * $scale));
        $target = imagecreatetruecolor($width, $height);
        if (! $target instanceof \GdImage) {
            throw new RuntimeException('Unable to allocate target image.');
        }
        imagealphablending($target, false);
        imagesavealpha($target, true);
        if (! imagecopyresampled($target, $image, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight)) {
            throw new RuntimeException('Unable to resize source image.');
        }

        return [$target, $width, $height];
    }

    private function encodeImage(\GdImage $image, string $format, int $quality): string
    {
        ob_start();

        try {
            $encoded = match ($format) {
                'jpg', 'jpeg' => imagejpeg($image, null, $quality),
                'webp' => imagewebp($image, null, $quality),
                'png' => imagepng($image),
                default => throw new RuntimeException("Unsupported output format [{$format}]."),
            };

            $contents = ob_get_clean();

            if ($encoded !== true) {
                throw new RuntimeException("Unable to encode image format [{$format}].");
            }

            return $contents;
        } catch (\Throwable $exception) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            throw $exception;
        }
    }

    /**
     * @param  array{width: int, height: int, fit: string, format: string, quality: int}  $config
     */
    private function markFailed(MediaAsset $asset, string $type, array $config): void
    {
        MediaVariant::query()->updateOrCreate(
            [
                'media_asset_id' => $asset->id,
                'variant_type' => $type,
                'locale' => null,
                'site_id' => null,
                'market_id' => null,
            ],
            [
                'disk' => $asset->disk,
                'path' => sprintf('media/variants/%s/%s.%s', $asset->uuid, $type, $config['format']),
                'format' => (string) $config['format'],
                'quality' => (int) $config['quality'],
                'status' => 'failed',
            ]
        );
    }
}
