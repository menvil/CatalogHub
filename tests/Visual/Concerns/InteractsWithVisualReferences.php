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
        $legacyName = $prefix.$state;
        $canonicalNames = [
            'central-login-desktop' => 'z-001__default__1280x900',
            'site-admin-login-desktop' => 'z-003__default__1280x900',
            'system-error-desktop' => 'z-007__central-500__1280x900',
            'system-error-mobile' => 'z-007__central-500__360x800',
            'admin-components-states-desktop' => 'z-008__empty-loading__1280x1000',
            'admin-components-actions-desktop' => 'z-009__action-progress__1280x1000',
            'component-gallery-wide' => 'z-010__wide__1440x1200',
        ];

        return dirname(__DIR__, 3).'/tests/Visual/baselines/'.($canonicalNames[$legacyName] ?? $legacyName).'.png';
    }

    protected function meanChannelDifference(string $reference, string $capture): float
    {
        $referenceImage = $this->decodePng($reference);
        $captureImage = $this->decodePng($capture);
        $this->assertSame(imagesx($referenceImage), imagesx($captureImage));
        $this->assertSame(imagesy($referenceImage), imagesy($captureImage));
        $difference = 0.0;
        $samples = 0;

        for ($y = 0; $y < imagesy($referenceImage); $y++) {
            for ($x = 0; $x < imagesx($referenceImage); $x++) {
                $referenceColor = $this->rgbAt($referenceImage, $x, $y);
                $captureColor = $this->rgbAt($captureImage, $x, $y);

                foreach (['red', 'green', 'blue'] as $channel) {
                    $difference += abs($referenceColor[$channel] - $captureColor[$channel]) / 255;
                    $samples++;
                }
            }
        }

        return $difference / $samples;
    }

    /** @return array{red: int, green: int, blue: int} */
    private function rgbAt(GdImage $image, int $x, int $y): array
    {
        $color = imagecolorat($image, $x, $y);

        if (! imageistruecolor($image)) {
            $paletteColor = imagecolorsforindex($image, $color);

            return [
                'red' => $paletteColor['red'],
                'green' => $paletteColor['green'],
                'blue' => $paletteColor['blue'],
            ];
        }

        return [
            'red' => ($color >> 16) & 0xFF,
            'green' => ($color >> 8) & 0xFF,
            'blue' => $color & 0xFF,
        ];
    }

    private function decodePng(string $path): GdImage
    {
        set_error_handler(static fn (): bool => true);

        try {
            $image = imagecreatefrompng($path);
        } finally {
            restore_error_handler();
        }

        $this->assertInstanceOf(GdImage::class, $image, "Unable to decode PNG [{$path}].");

        return $image;
    }
}
