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
     * @param array{width: int, height: int, fit: string, format: string, quality: int} $config
     */
    private function generate(MediaAsset $asset, string $type, array $config): void
    {
        $disk = Storage::disk($asset->disk);

        if (! $disk->exists($asset->original_path)) {
            $this->markFailed($asset, $type, $config);

            return;
        }

        try {
            $sourcePath = $disk->path($asset->original_path);
            [$image, $sourceWidth, $sourceHeight] = $this->loadImage($sourcePath, (string) $asset->mime_type);
            [$target, $width, $height] = $this->resize($image, $sourceWidth, $sourceHeight, $config);

            $format = strtolower((string) $config['format']);
            $path = sprintf(
                'media/variants/%s/%s.%s',
                $asset->uuid,
                $type,
                $format === 'jpg' ? 'jpg' : $format
            );

            ob_start();
            $this->outputImage($target, $format, (int) $config['quality']);
            $contents = (string) ob_get_clean();

            $disk->put($path, $contents);

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
                    'transform_hash' => sha1(json_encode($config, JSON_THROW_ON_ERROR)),
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
    private function loadImage(string $path, string $mime): array
    {
        $image = match ($mime) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => false,
        };

        if (! $image instanceof \GdImage) {
            throw new RuntimeException("Unsupported image mime [{$mime}].");
        }

        return [$image, imagesx($image), imagesy($image)];
    }

    /**
     * @param array{width: int, height: int, fit: string, format: string, quality: int} $config
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
            imagealphablending($target, false);
            imagesavealpha($target, true);
            imagecopyresampled($target, $image, $offsetX, $offsetY, 0, 0, $resizedWidth, $resizedHeight, $sourceWidth, $sourceHeight);

            return [$target, $targetWidth, $targetHeight];
        }

        $scale = min(1, $targetWidth / $sourceWidth, $targetHeight / $sourceHeight);
        $width = max(1, (int) floor($sourceWidth * $scale));
        $height = max(1, (int) floor($sourceHeight * $scale));
        $target = imagecreatetruecolor($width, $height);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $image, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        return [$target, $width, $height];
    }

    private function outputImage(\GdImage $image, string $format, int $quality): void
    {
        match ($format) {
            'jpg', 'jpeg' => imagejpeg($image, null, $quality),
            'webp' => imagewebp($image, null, $quality),
            'png' => imagepng($image),
            default => throw new RuntimeException("Unsupported output format [{$format}]."),
        };
    }

    /**
     * @param array{width: int, height: int, fit: string, format: string, quality: int} $config
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
