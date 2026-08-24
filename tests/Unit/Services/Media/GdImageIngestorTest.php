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
        $source = imagecreatetruecolor(4, 4);
        imagealphablending($source, false);
        imagesavealpha($source, true);
        imagefill($source, 0, 0, imagecolorallocatealpha($source, 20, 40, 60, 127));
        imagesetpixel($source, 1, 1, imagecolorallocatealpha($source, 200, 100, 50, 60));
        imagesetpixel($source, 2, 2, imagecolorallocatealpha($source, 0, 0, 0, 0));
        $bytes = $this->encode($source, 'png');
        imagedestroy($source);

        $normalized = app(GdImageIngestor::class)->ingest(new ImageInput($bytes, 'logo.png'));
        $decoded = imagecreatefromstring($normalized->bytes);

        $transparent = imagecolorsforindex($decoded, imagecolorat($decoded, 0, 0));
        $semiTransparent = imagecolorsforindex($decoded, imagecolorat($decoded, 1, 1));
        $opaque = imagecolorsforindex($decoded, imagecolorat($decoded, 2, 2));
        imagedestroy($decoded);

        $this->assertSame(127, $transparent['alpha']);
        $this->assertGreaterThan(0, $semiTransparent['alpha']);
        $this->assertSame(0, $opaque['alpha']);
    }

    public function test_extension_and_client_mime_are_not_authoritative(): void
    {
        $normalized = app(GdImageIngestor::class)->ingest(new ImageInput($this->imageBytes('png', 3, 2), 'logo.txt'));

        $this->assertSame('image/png', $normalized->mimeType);
        $this->assertSame('png', $normalized->canonicalExtension);
    }

    public function test_normalizes_exif_rotation_mirror_and_strips_metadata(): void
    {
        $source = imagecreatetruecolor(4, 2);
        imagefill($source, 0, 0, imagecolorallocate($source, 220, 20, 20));
        imagefilledrectangle($source, 2, 0, 3, 1, imagecolorallocate($source, 20, 20, 220));
        $jpeg = $this->encode($source, 'jpeg');
        imagedestroy($source);

        foreach ([6, 8] as $orientation) {
            $normalized = app(GdImageIngestor::class)->ingest(new ImageInput($this->withExifOrientation($jpeg, $orientation), 'rotated.jpg'));
            $this->assertSame(2, $normalized->width);
            $this->assertSame(4, $normalized->height);
            $metadata = @exif_read_data('data://image/jpeg;base64,'.base64_encode($normalized->bytes));
            $this->assertIsArray($metadata);
            $this->assertArrayNotHasKey('Orientation', $metadata);
            $this->assertArrayNotHasKey('GPSLatitude', $metadata);
        }

        $mirrored = app(GdImageIngestor::class)->ingest(new ImageInput($this->withExifOrientation($jpeg, 2), 'mirrored.jpg'));
        $decoded = imagecreatefromstring($mirrored->bytes);
        $left = imagecolorsforindex($decoded, imagecolorat($decoded, 0, 0));
        $right = imagecolorsforindex($decoded, imagecolorat($decoded, 3, 0));
        imagedestroy($decoded);

        $this->assertGreaterThan($left['red'], $left['blue']);
        $this->assertGreaterThan($right['blue'], $right['red']);
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
}
