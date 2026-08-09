<?php

declare(strict_types=1);

namespace Tests\Visual;

use GdImage;
use PHPUnit\Framework\TestCase;

final class AuthenticationScreensVisualTest extends TestCase
{
    private const MAX_MEAN_CHANNEL_DIFFERENCE = 0.03;

    /** @var array<string, array{width: int, height: int, path: string}> */
    private const STATES = [
        'central-login-desktop' => ['width' => 1280, 'height' => 900, 'path' => '/admin/central/login'],
        'central-login-mobile' => ['width' => 360, 'height' => 800, 'path' => '/admin/central/login'],
        'site-admin-login-desktop' => ['width' => 1280, 'height' => 900, 'path' => '/admin/site/login'],
        'site-admin-login-mobile' => ['width' => 360, 'height' => 800, 'path' => '/admin/site/login'],
    ];

    public function test_approved_login_reference_checksums_are_unchanged(): void
    {
        foreach (array_keys(self::STATES) as $state) {
            $reference = $this->referencePath($state);

            $this->assertFileExists($reference);
            $this->assertFileExists($reference.'.sha256');
            $this->assertSame(trim((string) file_get_contents($reference.'.sha256')), hash_file('sha256', $reference));
        }
    }

    public function test_current_login_screens_match_all_approved_references(): void
    {
        $this->withServer(function (int $port, string $log): void {
            foreach (self::STATES as $state => $configuration) {
                $temporaryPath = tempnam(sys_get_temp_dir(), 'cataloghub-auth-screen-');
                $this->assertIsString($temporaryPath);
                @unlink($temporaryPath);
                $capture = $temporaryPath.'.png';

                try {
                    $this->capture($port, $capture, $configuration);
                    $this->assertSame([$configuration['width'], $configuration['height']], array_slice(getimagesize($capture) ?: [], 0, 2));
                    $this->assertLessThanOrEqual(
                        self::MAX_MEAN_CHANNEL_DIFFERENCE,
                        $this->meanChannelDifference($this->referencePath($state), $capture),
                        "Authentication state [{$state}] differs from its approved reference. Server log: ".(string) file_get_contents($log),
                    );
                } finally {
                    @unlink($capture);
                }
            }
        });
    }

    /** @param array{width: int, height: int, path: string} $configuration */
    private function capture(int $port, string $capture, array $configuration): void
    {
        $browser = proc_open([
            'node',
            dirname(__DIR__, 2).'/tests/Support/capture-chrome.mjs',
            $this->requiredChromeBinary(),
            "http://127.0.0.1:{$port}{$configuration['path']}",
            $capture,
            (string) $configuration['width'],
            (string) $configuration['height'],
        ], $this->descriptors(), $pipes, dirname(__DIR__, 2));

        $this->assertIsResource($browser, 'Unable to start Google Chrome.');
        $this->assertSame(0, proc_close($browser));
        $this->assertFileExists($capture);
    }

    private function withServer(callable $callback): void
    {
        $root = dirname(__DIR__, 2);
        $log = tempnam(sys_get_temp_dir(), 'cataloghub-auth-server-');
        $this->assertIsString($log);
        $environment = getenv();
        $environment['APP_ENV'] = 'testing';
        $server = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:0', '-t', $root.'/public', $root.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],
            $this->descriptors($log),
            $pipes,
            $root.'/public',
            $environment,
        );

        if (! is_resource($server)) {
            @unlink($log);
            $this->fail('Unable to start the authentication visual server.');
        }

        try {
            $port = $this->waitForServerPort($server, $log);
            $this->waitForLogin($port);
            $callback($port, $log);
        } finally {
            proc_terminate($server);
            proc_close($server);
            @unlink($log);
        }
    }

    /** @return array<int, array{string, string, string}> */
    private function descriptors(?string $log = null): array
    {
        $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';

        return [
            0 => ['file', $null, 'r'],
            1 => ['file', $log ?? $null, $log === null ? 'w' : 'a'],
            2 => ['file', $log ?? $null, $log === null ? 'w' : 'a'],
        ];
    }

    /** @param resource $server */
    private function waitForServerPort($server, string $log): int
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $output = (string) file_get_contents($log);

            if (preg_match('/Development Server \(http:\/\/127\.0\.0\.1:(\d+)\) started/', $output, $matches) === 1) {
                return (int) $matches[1];
            }

            if (! proc_get_status($server)['running']) {
                $this->fail('Authentication visual server stopped before binding: '.$output);
            }

            usleep(100_000);
        }

        $this->fail('Authentication visual server did not report its assigned port.');
    }

    private function waitForLogin(int $port): void
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            set_error_handler(static fn (): bool => true);

            try {
                $html = file_get_contents("http://127.0.0.1:{$port}/admin/central/login");
            } finally {
                restore_error_handler();
            }

            if (is_string($html) && str_contains($html, 'data-auth-screen="central-login"')) {
                return;
            }

            usleep(100_000);
        }

        $this->fail('Authentication visual server did not render the Central login screen.');
    }

    private function requiredChromeBinary(): string
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

        $this->markTestSkipped('Google Chrome is required for deterministic authentication visual acceptance.');
    }

    private function referencePath(string $state): string
    {
        return dirname(__DIR__, 2)."/tests/Visual/baselines/{$state}.png";
    }

    private function meanChannelDifference(string $reference, string $capture): float
    {
        $referenceImage = imagecreatefrompng($reference);
        $captureImage = imagecreatefrompng($capture);
        $this->assertInstanceOf(GdImage::class, $referenceImage);
        $this->assertInstanceOf(GdImage::class, $captureImage);
        $this->assertSame(imagesx($referenceImage), imagesx($captureImage));
        $this->assertSame(imagesy($referenceImage), imagesy($captureImage));
        $difference = 0.0;
        $samples = 0;

        for ($y = 0; $y < imagesy($referenceImage); $y += 2) {
            for ($x = 0; $x < imagesx($referenceImage); $x += 2) {
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
