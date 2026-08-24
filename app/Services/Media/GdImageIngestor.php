<?php

namespace App\Services\Media;

use GdImage;

final class GdImageIngestor implements ImageIngestor
{
    public function ingest(ImageInput $input): NormalizedImage
    {
        if ($input->byteSize() > (int) config('media.max_upload_bytes')) {
            throw new ImageIngestException('The image is too large.');
        }

        $info = @getimagesizefromstring($input->bytes);
        $finfoMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($input->bytes);
        if ($info === false || ! is_string($finfoMime)) {
            throw new ImageIngestException('The image could not be decoded safely.');
        }

        $mime = match ((int) $info[2]) {
            IMAGETYPE_JPEG => 'image/jpeg', IMAGETYPE_PNG => 'image/png', IMAGETYPE_WEBP => 'image/webp', default => null,
        };
        if ($mime === null || $mime !== $finfoMime || ! in_array($mime, config('media.allowed_upload_mimes'), true)) {
            throw new ImageIngestException('Unsupported image format.');
        }

        $width = (int) $info[0];
        $height = (int) $info[1];
        if ($width < 1 || $height < 1 || $width > (int) config('media.max_upload_width') || $height > (int) config('media.max_upload_height') || $width * $height > (int) config('media.max_upload_pixels')) {
            throw new ImageIngestException('The image exceeds allowed dimensions.');
        }
        $image = @imagecreatefromstring($input->bytes);
        if (! $image instanceof GdImage) {
            throw new ImageIngestException('The image could not be decoded safely.');
        }
        if ($mime === 'image/jpeg') {
            $image = $this->orient($image, $input->bytes);
        }
        $width = imagesx($image);
        $height = imagesy($image);
        $bytes = $this->encode($image, $mime);
        imagedestroy($image);
        $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];

        return new NormalizedImage($bytes, $mime, $extension, $width, $height, strlen($bytes), 'sha256:'.hash('sha256', $bytes));
    }

    private function orient(GdImage $image, string $bytes): GdImage
    {
        $tmp = tmpfile();
        if ($tmp === false) {
            return $image;
        }
        fwrite($tmp, $bytes);
        $meta = stream_get_meta_data($tmp);
        $orientation = @exif_read_data($meta['uri'])['Orientation'] ?? 1;
        fclose($tmp);
        $flip = static function (GdImage $i, int $mode): GdImage {
            imageflip($i, $mode);

            return $i;
        };
        $result = match ((int) $orientation) {
            2 => $flip($image, IMG_FLIP_HORIZONTAL), 3 => imagerotate($image, 180, 0), 4 => $flip($image, IMG_FLIP_VERTICAL),
            5 => $flip(imagerotate($image, -90, 0), IMG_FLIP_HORIZONTAL), 6 => imagerotate($image, -90, 0),
            7 => $flip(imagerotate($image, 90, 0), IMG_FLIP_HORIZONTAL), 8 => imagerotate($image, 90, 0), default => $image,
        };
        if ($result !== $image) {
            imagedestroy($image);
        }

        return $result;
    }

    private function encode(GdImage $image, string $mime): string
    {
        ob_start();
        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($image, null, (int) config('media.jpeg_quality')),
            'image/png' => imagepng($image, null, (int) config('media.png_compression')),
            'image/webp' => imagewebp($image, null, (int) config('media.webp_quality')),
        };
        $bytes = (string) ob_get_clean();
        if (! $ok || $bytes === '') {
            throw new ImageIngestException('The image could not be normalized safely.');
        }

        return $bytes;
    }
}
