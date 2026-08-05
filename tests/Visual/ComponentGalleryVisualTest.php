<?php

declare(strict_types=1);

namespace Tests\Visual;

use GdImage;
use PHPUnit\Framework\TestCase;

final class ComponentGalleryVisualTest extends TestCase
{
    public function test_approved_gallery_reference_checksum_is_unchanged(): void
    {
        $root = dirname(__DIR__, 2);
        $reference = $root.'/tests/Visual/baselines/component-gallery-wide.png';
        $checksum = $reference.'.sha256';

        $this->assertFileExists($reference);
        $this->assertFileExists($checksum);
        $this->assertSame(trim((string) file_get_contents($checksum)), hash_file('sha256', $reference));
    }

    public function test_current_gallery_matches_the_approved_wide_reference(): void
    {
        $root = dirname(__DIR__, 2);
        $reference = $root.'/tests/Visual/baselines/component-gallery-wide.png';
        $temporaryPath = tempnam(sys_get_temp_dir(), 'cataloghub-gallery-');

        $this->assertIsString($temporaryPath);
        @unlink($temporaryPath);
        $capture = $temporaryPath.'.png';

        try {
            $this->captureGallery($root, $capture);
            $this->assertSame([1440, 1200], array_slice(getimagesize($capture) ?: [], 0, 2));
            $this->assertLessThanOrEqual(0.03, $this->meanChannelDifference($reference, $capture));
        } finally {
            @unlink($capture);
        }
    }

    private function captureGallery(string $root, string $capture): void
    {
        $chrome = $this->chromeBinary();
        $this->assertNotNull($chrome, 'Google Chrome is required for the deterministic visual regression capture.');
        $log = tempnam(sys_get_temp_dir(), 'cataloghub-gallery-server-');
        $this->assertIsString($log);
        $nullDevice = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $descriptors = [
            0 => ['file', $nullDevice, 'r'],
            1 => ['file', $log, 'a'],
            2 => ['file', $log, 'a'],
        ];
        $environment = getenv();
        $environment['APP_ENV'] = 'testing';
        $server = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:0', '-t', $root.'/public', $root.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],
            $descriptors,
            $pipes,
            $root.'/public',
            $environment,
        );

        if (! is_resource($server)) {
            @unlink($log);
            $this->fail('Unable to start the deterministic gallery server.');
        }

        try {
            $port = $this->waitForServerPort($server, $log);
            $this->waitForGallery($port, $log);
            $browser = proc_open([
                $chrome,
                '--headless=new',
                '--disable-gpu',
                '--hide-scrollbars',
                '--force-device-scale-factor=1',
                '--window-size=1440,1200',
                '--virtual-time-budget=2000',
                "--screenshot={$capture}",
                "http://127.0.0.1:{$port}/dev/component-gallery",
            ], $descriptors, $browserPipes, $root);

            $this->assertIsResource($browser, 'Unable to start Google Chrome.');
            $this->assertSame(0, proc_close($browser), (string) file_get_contents($log));
            $this->assertFileExists($capture);
        } finally {
            proc_terminate($server);
            proc_close($server);
            @unlink($log);
        }
    }

    private function chromeBinary(): ?string
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

        return null;
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
                $this->fail('Gallery server stopped before binding: '.$output);
            }

            usleep(100_000);
        }

        $this->fail('Gallery server did not report its assigned port: '.(string) file_get_contents($log));
    }

    private function waitForGallery(int $port, string $log): void
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            set_error_handler(static fn (): bool => true);

            try {
                $connection = stream_socket_client("tcp://127.0.0.1:{$port}", $errorCode, $errorMessage, 0.1);
            } finally {
                restore_error_handler();
            }

            if (is_resource($connection)) {
                fwrite($connection, "GET /dev/component-gallery HTTP/1.1\r\nHost: 127.0.0.1\r\nConnection: close\r\n\r\n");
                stream_set_timeout($connection, 1);
                $response = stream_get_contents($connection);
                fclose($connection);

                if (is_string($response) && str_contains($response, 'data-presentation-context="central-admin"')) {
                    return;
                }
            }

            usleep(100_000);
        }

        $this->fail('The assigned server did not render the gallery: '.(string) file_get_contents($log));
    }

    private function meanChannelDifference(string $reference, string $capture): float
    {
        $referenceImage = imagecreatefrompng($reference);
        $captureImage = imagecreatefrompng($capture);
        $this->assertInstanceOf(GdImage::class, $referenceImage);
        $this->assertInstanceOf(GdImage::class, $captureImage);
        $this->assertSame(imagesx($referenceImage), imagesx($captureImage));
        $this->assertSame(imagesy($referenceImage), imagesy($captureImage));
        $difference = 0;
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
