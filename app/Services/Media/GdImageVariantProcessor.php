<?php

namespace App\Services\Media;

use GdImage;
use RuntimeException;

final class GdImageVariantProcessor implements ImageVariantProcessor
{
    public function process(string $source, MediaVariantSpecification $spec): array
    {
        $image = @imagecreatefromstring($source);
        if (! $image instanceof GdImage) {
            throw new RuntimeException('Unable to decode media original.');
        }
        $sw = imagesx($image);
        $sh = imagesy($image);
        $scale = $spec->fit === 'cover' ? max($spec->width / $sw, $spec->height / $sh) : min($spec->width / $sw, $spec->height / $sh);
        $canCover = $spec->fit === 'cover' && ($spec->allowUpscale || $scale <= 1);
        if (! $spec->allowUpscale) {
            $scale = min(1, $scale);
        }
        $resizedW = max(1, (int) ceil($sw * $scale));
        $resizedH = max(1, (int) ceil($sh * $scale));
        $w = $canCover ? $spec->width : $resizedW;
        $h = $canCover ? $spec->height : $resizedH;
        // If cover would need enlargement, use the bounded source instead of manufacturing pixels.
        $target = imagecreatetruecolor($w, $h);
        if (! $target instanceof GdImage) {
            throw new RuntimeException('Unable to allocate variant.');
        }
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $clear = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefill($target, 0, 0, $clear);
        $dx = $canCover ? (int) floor(($w - $resizedW) / 2) : 0;
        $dy = $canCover ? (int) floor(($h - $resizedH) / 2) : 0;
        imagecopyresampled($target, $image, $dx, $dy, 0, 0, $resizedW, $resizedH, $sw, $sh);
        ob_start();
        $ok = match ($spec->format) {
            'jpg', 'jpeg' => imagejpeg($target, null, $spec->quality), 'png' => imagepng($target), 'webp' => imagewebp($target, null, $spec->quality), default => false
        };
        $bytes = (string) ob_get_clean();
        imagedestroy($image);
        imagedestroy($target);
        if (! $ok || $bytes === '') {
            throw new RuntimeException('Unable to encode variant.');
        }

        return ['bytes' => $bytes, 'width' => $w, 'height' => $h];
    }
}
