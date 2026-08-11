<?php

declare(strict_types=1);

namespace Tests\Visual\Concerns;

use GdImage;

trait InteractsWithVisualReferences
{
    /** @return array<int, array{string, string, string}> */
    protected function descriptors(?string $log = null): array
    {
        $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';

        return [
            0 => ['file', $null, 'r'],
            1 => ['file', $log ?? $null, $log === null ? 'w' : 'a'],
            2 => ['file', $log ?? $null, $log === null ? 'w' : 'a'],
        ];
    }

    protected function requiredChromeBinary(): string
    {
        $configured = getenv('CHROME_BIN');
        $candidates = array_filter([
            is_string($configured) ? $configured : null,
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
        ]);

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        $this->markTestSkipped('Google Chrome is required for deterministic visual acceptance.');
    }

    protected function referencePath(string $state, string $prefix = ''): string
    {
        return dirname(__DIR__, 3)."/tests/Visual/baselines/{$prefix}{$state}.png";
    }

    protected function meanChannelDifference(string $reference, string $capture): float
    {
        $referenceImage = imagecreatefrompng($reference);
        $captureImage = imagecreatefrompng($capture);
        $this->assertInstanceOf(GdImage::class, $referenceImage);
        $this->assertInstanceOf(GdImage::class, $captureImage);
        $this->assertSame(imagesx($referenceImage), imagesx($captureImage));
        $this->assertSame(imagesy($referenceImage), imagesy($captureImage));
        $difference = 0.0;
        $samples = 0;

        for ($y = 0; $y < imagesy($referenceImage); $y++) {
            for ($x = 0; $x < imagesx($referenceImage); $x++) {
                $referenceColor = imagecolorat($referenceImage, $x, $y);
                $captureColor = imagecolorat($captureImage, $x, $y);

                foreach ([16, 8, 0] as $shift) {
                    $difference += abs((($referenceColor >> $shift) & 0xFF) - (($captureColor >> $shift) & 0xFF)) / 255;
                    $samples++;
                }
            }
        }

        return $difference / $samples;
    }
}
