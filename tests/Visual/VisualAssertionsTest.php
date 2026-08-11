<?php

declare(strict_types=1);

namespace Tests\Visual;

use GdImage;
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
        $current = $this->png('capture.png', [255, 255, 255]);

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
        self::assertFileExists($this->directory.'/artifacts/diff/mismatched-state.png');
    }

    /** @param array{int, int, int} $rgb */
    private function png(string $name, array $rgb): string
    {
        $path = $this->directory.'/'.$name;
        $image = imagecreatetruecolor(4, 4);
        self::assertInstanceOf(GdImage::class, $image);
        imagefill($image, 0, 0, imagecolorallocate($image, ...$rgb));
        imagepng($image, $path);

        return $path;
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
