<?php

declare(strict_types=1);

namespace Tests\Visual;

use GdImage;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Tests\Visual\Concerns\InteractsWithVisualReferences;

final class InteractsWithVisualReferencesTest extends TestCase
{
    use InteractsWithVisualReferences;

    public function test_equivalent_indexed_and_true_color_pngs_have_no_rgb_difference(): void
    {
        $indexedPath = tempnam(sys_get_temp_dir(), 'cataloghub-indexed-');
        $trueColorPath = tempnam(sys_get_temp_dir(), 'cataloghub-true-color-');
        $this->assertIsString($indexedPath);
        $this->assertIsString($trueColorPath);
        $indexed = imagecreate(2, 1);
        $trueColor = imagecreatetruecolor(2, 1);
        $this->assertInstanceOf(GdImage::class, $indexed);
        $this->assertInstanceOf(GdImage::class, $trueColor);
        $red = imagecolorallocate($indexed, 210, 30, 45);
        $blue = imagecolorallocate($indexed, 20, 70, 160);
        imagesetpixel($indexed, 0, 0, $red);
        imagesetpixel($indexed, 1, 0, $blue);
        imagesetpixel($trueColor, 0, 0, (210 << 16) | (30 << 8) | 45);
        imagesetpixel($trueColor, 1, 0, (20 << 16) | (70 << 8) | 160);
        imagepng($indexed, $indexedPath);
        imagepng($trueColor, $trueColorPath);

        try {
            $this->assertSame(0.0, $this->meanChannelDifference($indexedPath, $trueColorPath));
        } finally {
            @unlink($indexedPath);
            @unlink($trueColorPath);
        }
    }

    public function test_decode_failures_are_reported_as_assertion_failures(): void
    {
        $invalidPath = tempnam(sys_get_temp_dir(), 'cataloghub-invalid-png-');
        $this->assertIsString($invalidPath);
        file_put_contents($invalidPath, 'not a png');

        try {
            $this->expectException(AssertionFailedError::class);
            $this->meanChannelDifference($invalidPath, $invalidPath);
        } finally {
            @unlink($invalidPath);
        }
    }

    public function test_dimension_mismatches_are_rejected(): void
    {
        $firstPath = tempnam(sys_get_temp_dir(), 'cataloghub-first-png-');
        $secondPath = tempnam(sys_get_temp_dir(), 'cataloghub-second-png-');
        $this->assertIsString($firstPath);
        $this->assertIsString($secondPath);
        $first = imagecreatetruecolor(1, 1);
        $second = imagecreatetruecolor(2, 1);
        $this->assertInstanceOf(GdImage::class, $first);
        $this->assertInstanceOf(GdImage::class, $second);
        imagepng($first, $firstPath);
        imagepng($second, $secondPath);

        try {
            $this->expectException(AssertionFailedError::class);
            $this->meanChannelDifference($firstPath, $secondPath);
        } finally {
            @unlink($firstPath);
            @unlink($secondPath);
        }
    }
}
