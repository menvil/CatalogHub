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

        if ($chrome === null) {
            $this->markTestSkipped('Google Chrome is required for the deterministic visual regression capture.');
        }

        $port = $this->availablePort();
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
        $environment['APP_URL'] = "http://127.0.0.1:{$port}";
        $server = proc_open(
            [PHP_BINARY, $root.'/artisan', 'serve', '--host=127.0.0.1', "--port={$port}"],
            $descriptors,
            $pipes,
            $root,
            $environment,
        );

        $this->assertIsResource($server, 'Unable to start the deterministic gallery server.');

        try {
            $this->waitForServer($port, $log);
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

    private function availablePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        $this->assertIsResource($socket, "Unable to reserve a visual-test port: {$errorCode} {$errorMessage}");
        $address = stream_socket_get_name($socket, false);
        fclose($socket);

        $this->assertIsString($address);

        return (int) substr($address, (int) strrpos($address, ':') + 1);
    }

    private function waitForServer(int $port, string $log): void
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $connection = @fsockopen('127.0.0.1', $port, $errorCode, $errorMessage, 0.1);

            if (is_resource($connection)) {
                fclose($connection);

                return;
            }

            usleep(100_000);
        }

        $this->fail('Gallery server did not start: '.(string) file_get_contents($log));
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
