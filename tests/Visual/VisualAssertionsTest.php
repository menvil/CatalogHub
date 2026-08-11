<?php

declare(strict_types=1);

namespace Tests\Visual;

use GdImage;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\VisualAssertions;

final class VisualAssertionsTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/cataloghub-visual-assertions-'.getmypid();
        $this->removeArtifacts();
        mkdir($this->directory, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeArtifacts();
    }

    public function test_matching_capture_passes_and_is_preserved_separately_from_baseline(): void
    {
        $baseline = $this->png('baseline.png', [20, 40, 60]);
        $current = $this->png('capture.png', [20, 40, 60]);

        self::assertSame(0.0, VisualAssertions::compare(
            $baseline,
            $current,
            $this->directory.'/artifacts',
            'matching-state',
            0.0,
        ));
        self::assertFileExists($this->directory.'/artifacts/current/matching-state.png');
        self::assertFileDoesNotExist($this->directory.'/artifacts/diff/matching-state.png');
        self::assertFileExists($baseline);
    }

    public function test_intentional_mismatch_fails_and_writes_a_visible_diff(): void
    {
        $baseline = $this->png('baseline.png', [0, 0, 0]);
        $current = $this->png('capture.png', [0, 0, 0], pixels: [[1, 1, [255, 255, 255]]]);

        try {
            VisualAssertions::compare(
                $baseline,
                $current,
                $this->directory.'/artifacts',
                'mismatched-state',
                0.0,
            );
            self::fail('Intentional visual mismatch passed.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('Visual mismatch', $exception->getMessage());
        }

        self::assertFileExists($this->directory.'/artifacts/current/mismatched-state.png');
        $diffPath = $this->directory.'/artifacts/diff/mismatched-state.png';
        self::assertFileExists($diffPath);

        $diff = imagecreatefrompng($diffPath);
        self::assertInstanceOf(GdImage::class, $diff);
        self::assertSame([0, 0, 0], $this->rgbAt($diff, 0, 0));
        self::assertSame([255, 0, 0], $this->rgbAt($diff, 1, 1));
    }

    public function test_invalid_artifact_name_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('lower-case kebab-case');

        VisualAssertions::compare('unused', 'unused', $this->directory.'/artifacts', 'Invalid Name', 0.0);
    }

    public function test_dimension_mismatch_preserves_current_capture_before_failing(): void
    {
        $baseline = $this->png('baseline.png', [0, 0, 0], width: 4, height: 4);
        $current = $this->png('capture.png', [0, 0, 0], width: 5, height: 3);

        try {
            VisualAssertions::compare(
                $baseline,
                $current,
                $this->directory.'/artifacts',
                'dimension-mismatch',
                0.0,
            );
            self::fail('Dimension mismatch passed.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('dimensions differ', $exception->getMessage());
        }

        self::assertFileExists($this->directory.'/artifacts/current/dimension-mismatch.png');
        self::assertFileDoesNotExist($this->directory.'/artifacts/diff/dimension-mismatch.png');
    }

    public function test_non_zero_tolerance_accepts_small_difference_and_rejects_larger_difference(): void
    {
        $baseline = $this->png('baseline.png', [0, 0, 0], width: 10, height: 10);
        $smallDifference = $this->png(
            'small-difference.png',
            [0, 0, 0],
            width: 10,
            height: 10,
            pixels: [[0, 0, [30, 0, 0]]],
        );
        $largeDifference = $this->png(
            'large-difference.png',
            [0, 0, 0],
            width: 10,
            height: 10,
            pixels: [[0, 0, [255, 255, 255]]],
        );

        $difference = VisualAssertions::compare(
            $baseline,
            $smallDifference,
            $this->directory.'/artifacts',
            'tolerated-difference',
            0.001,
        );

        self::assertGreaterThan(0.0, $difference);
        self::assertLessThan(0.001, $difference);
        self::assertFileDoesNotExist($this->directory.'/artifacts/diff/tolerated-difference.png');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Visual mismatch');

        VisualAssertions::compare(
            $baseline,
            $largeDifference,
            $this->directory.'/artifacts',
            'excessive-difference',
            0.001,
        );
    }

    /**
     * @param  array{int, int, int}  $rgb
     * @param  list<array{int, int, array{int, int, int}}>  $pixels
     */
    private function png(
        string $name,
        array $rgb,
        int $width = 4,
        int $height = 4,
        array $pixels = [],
    ): string {
        $path = $this->directory.'/'.$name;
        $image = imagecreatetruecolor($width, $height);
        self::assertInstanceOf(GdImage::class, $image);
        imagefill($image, 0, 0, imagecolorallocate($image, ...$rgb));

        foreach ($pixels as [$x, $y, $pixelRgb]) {
            imagesetpixel($image, $x, $y, imagecolorallocate($image, ...$pixelRgb));
        }

        self::assertTrue(imagepng($image, $path));

        return $path;
    }

    /** @return array{int, int, int} */
    private function rgbAt(GdImage $image, int $x, int $y): array
    {
        $color = imagecolorat($image, $x, $y);

        return [($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF];
    }

    private function removeArtifacts(): void
    {
        if (! is_dir($this->directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($this->directory);
    }
}
