<?php

namespace Tests\Unit\Services\Media;

use App\Services\Media\GdImageIngestor;
use App\Services\Media\ImageIngestException;
use App\Services\Media\ImageInput;
use GdImage;
use Tests\TestCase;

final class GdImageIngestorTest extends TestCase
{
    public function test_normalizes_jpeg_png_and_webp_from_their_bytes(): void
    {
        foreach ([
            ['jpeg', 'image/jpeg', 'jpg'],
            ['png', 'image/png', 'png'],
            ['webp', 'image/webp', 'webp'],
        ] as [$format, $mime, $extension]) {
            $normalized = app(GdImageIngestor::class)->ingest(new ImageInput($this->imageBytes($format, 7, 5), "spoofed.{$format}"));

            $this->assertSame($mime, $normalized->mimeType);
            $this->assertSame($extension, $normalized->canonicalExtension);
            $this->assertSame(7, $normalized->width);
            $this->assertSame(5, $normalized->height);
            $this->assertSame(strlen($normalized->bytes), $normalized->byteSize);
            $this->assertSame('sha256:'.hash('sha256', $normalized->bytes), $normalized->checksum);
            $this->assertNotFalse(imagecreatefromstring($normalized->bytes));
        }
    }

    public function test_png_alpha_is_preserved_during_normalization(): void
    {
        foreach (['png', 'webp'] as $format) {
            $source = imagecreatetruecolor(4, 4);
            imagealphablending($source, false);
            imagesavealpha($source, true);
            imagefill($source, 0, 0, imagecolorallocatealpha($source, 20, 40, 60, 127));
            imagesetpixel($source, 1, 1, imagecolorallocatealpha($source, 200, 100, 50, 60));
            imagesetpixel($source, 2, 2, imagecolorallocatealpha($source, 0, 0, 0, 0));
            $bytes = $this->encode($source, $format);
            imagedestroy($source);

            $normalized = app(GdImageIngestor::class)->ingest(new ImageInput($bytes, "logo.{$format}"));
            $decoded = imagecreatefromstring($normalized->bytes);
            $this->assertInstanceOf(GdImage::class, $decoded);

            $transparent = imagecolorsforindex($decoded, imagecolorat($decoded, 0, 0));
            $semiTransparent = imagecolorsforindex($decoded, imagecolorat($decoded, 1, 1));
            $opaque = imagecolorsforindex($decoded, imagecolorat($decoded, 2, 2));
            imagedestroy($decoded);

            $this->assertSame(127, $transparent['alpha'], "{$format} must preserve fully transparent pixels.");
            $this->assertGreaterThan(0, $semiTransparent['alpha'], "{$format} must preserve semi-transparent pixels.");
            $this->assertLessThan(127, $semiTransparent['alpha'], "{$format} semi-transparent pixels must not become fully transparent.");
            $this->assertSame(0, $opaque['alpha'], "{$format} must preserve opaque pixels.");
        }
    }

    public function test_extension_and_client_mime_are_not_authoritative(): void
    {
        $normalized = app(GdImageIngestor::class)->ingest(new ImageInput($this->imageBytes('png', 3, 2), 'logo.txt'));

        $this->assertSame('image/png', $normalized->mimeType);
        $this->assertSame('png', $normalized->canonicalExtension);
    }

    public function test_normalizes_exif_rotation_mirror_and_strips_metadata(): void
    {
        $source = imagecreatetruecolor(40, 20);
        imagefilledrectangle($source, 0, 0, 19, 9, imagecolorallocate($source, 220, 20, 20));
        imagefilledrectangle($source, 20, 0, 39, 9, imagecolorallocate($source, 20, 220, 20));
        imagefilledrectangle($source, 0, 10, 19, 19, imagecolorallocate($source, 20, 20, 220));
        imagefilledrectangle($source, 20, 10, 39, 19, imagecolorallocate($source, 220, 220, 20));
        $jpeg = $this->encode($source, 'jpeg');
        imagedestroy($source);

        foreach ([
            2 => [['green', 'red'], ['yellow', 'blue']],
            3 => [['yellow', 'blue'], ['green', 'red']],
            4 => [['blue', 'yellow'], ['red', 'green']],
            5 => [['red', 'blue'], ['green', 'yellow']],
            6 => [['blue', 'red'], ['yellow', 'green']],
            7 => [['yellow', 'green'], ['blue', 'red']],
            8 => [['green', 'yellow'], ['red', 'blue']],
        ] as $orientation => $expectedPixels) {
            $normalized = app(GdImageIngestor::class)->ingest(new ImageInput($this->withExifOrientation($jpeg, $orientation), 'rotated.jpg'));
            $rotates = in_array($orientation, [5, 6, 7, 8], true);
            $this->assertSame($rotates ? 20 : 40, $normalized->width);
            $this->assertSame($rotates ? 40 : 20, $normalized->height);
            $metadata = @exif_read_data('data://image/jpeg;base64,'.base64_encode($normalized->bytes));
            $this->assertIsArray($metadata);
            $this->assertArrayNotHasKey('Orientation', $metadata);
            $this->assertArrayNotHasKey('GPSLatitude', $metadata);
            $decoded = imagecreatefromstring($normalized->bytes);
            $this->assertInstanceOf(GdImage::class, $decoded);
            $this->assertSame($expectedPixels, $this->quadrantLabels($decoded));
            imagedestroy($decoded);
        }
    }

    public function test_rejects_spoofed_corrupt_and_unsupported_inputs(): void
    {
        $jpeg = $this->imageBytes('jpeg', 10, 8);
        $gif = $this->imageBytes('gif', 2, 2);

        foreach (['plain text', substr($jpeg, 0, 30), $gif, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>'] as $bytes) {
            try {
                app(GdImageIngestor::class)->ingest(new ImageInput($bytes, 'logo.png'));
                $this->fail('Untrusted input must not be normalized.');
            } catch (ImageIngestException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_enforces_byte_width_height_and_independent_pixel_limits(): void
    {
        $this->assertRejectedWith(['media.max_upload_bytes' => 10], $this->imageBytes('png', 4, 4));
        $this->assertRejectedWith(['media.max_upload_width' => 3], $this->imageBytes('png', 4, 3));
        $this->assertRejectedWith(['media.max_upload_height' => 3], $this->imageBytes('png', 3, 4));
        $this->assertRejectedWith([
            'media.max_upload_width' => 100,
            'media.max_upload_height' => 100,
            'media.max_upload_pixels' => 5_000,
        ], $this->imageBytes('png', 80, 80));
    }

    /** @param array<string, int> $overrides */
    private function assertRejectedWith(array $overrides, string $bytes): void
    {
        $original = [];
        foreach ($overrides as $key => $value) {
            $original[$key] = config($key);
            config([$key => $value]);
        }

        try {
            app(GdImageIngestor::class)->ingest(new ImageInput($bytes, 'logo.png'));
            $this->fail('Expected configured ingest limit to reject the image.');
        } catch (ImageIngestException) {
            $this->addToAssertionCount(1);
        } finally {
            foreach ($original as $key => $value) {
                config([$key => $value]);
            }
        }
    }

    private function imageBytes(string $format, int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 32, 96, 192));
        $bytes = $this->encode($image, $format);
        imagedestroy($image);

        return $bytes;
    }

    private function encode(GdImage $image, string $format): string
    {
        ob_start();
        $ok = match ($format) {
            'jpeg' => imagejpeg($image, null, 90),
            'png' => imagepng($image, null, 6),
            'webp' => imagewebp($image, null, 90),
            'gif' => imagegif($image),
            default => throw new \InvalidArgumentException("Unsupported test format [{$format}]."),
        };
        $bytes = (string) ob_get_clean();
        $this->assertTrue($ok);

        return $bytes;
    }

    private function withExifOrientation(string $jpeg, int $orientation): string
    {
        $tiff = "MM\x00\x2A\x00\x00\x00\x08\x00\x01\x01\x12\x00\x03\x00\x00\x00\x01"
            .pack('n', $orientation)."\x00\x00\x00\x00\x00\x00";
        $app1 = "\xFF\xE1".pack('n', strlen($tiff) + 8)."Exif\x00\x00".$tiff;

        return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
    }

    /** @return list<list<string>> */
    private function quadrantLabels(GdImage $image): array
    {
        $labels = [];
        for ($row = 0; $row < 2; $row++) {
            for ($column = 0; $column < 2; $column++) {
                $color = imagecolorsforindex(
                    $image,
                    imagecolorat($image, (int) ((2 * $column + 1) * imagesx($image) / 4), (int) ((2 * $row + 1) * imagesy($image) / 4)),
                );
                $labels[$row][$column] = match (true) {
                    $color['red'] > 120 && $color['green'] > 120 && $color['blue'] < 120 => 'yellow',
                    $color['red'] > $color['green'] + 50 && $color['red'] > $color['blue'] + 50 => 'red',
                    $color['green'] > $color['red'] + 50 && $color['green'] > $color['blue'] + 50 => 'green',
                    $color['blue'] > $color['red'] + 50 && $color['blue'] > $color['green'] + 50 => 'blue',
                    default => 'unknown',
                };
            }
        }

        return $labels;
    }
}
