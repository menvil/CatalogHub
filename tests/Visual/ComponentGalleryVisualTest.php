<?php

declare(strict_types=1);

namespace Tests\Visual;

use GdImage;
use PHPUnit\Framework\TestCase;

final class ComponentGalleryVisualTest extends TestCase
{
    private const MAX_MEAN_CHANNEL_DIFFERENCE = 0.04;

    /** @var array<string, array{width: int, height: int, section: string}> */
    private const COMPONENT_STATES = [
        'forms-desktop' => ['width' => 1280, 'height' => 1000, 'section' => 'forms'],
        'forms-mobile' => ['width' => 360, 'height' => 900, 'section' => 'forms'],
        'tables-desktop' => ['width' => 1280, 'height' => 1000, 'section' => 'tables'],
        'tables-mobile' => ['width' => 360, 'height' => 900, 'section' => 'tables'],
        'feedback-desktop' => ['width' => 1280, 'height' => 1000, 'section' => 'feedback'],
        'feedback-mobile' => ['width' => 360, 'height' => 900, 'section' => 'feedback'],
    ];

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
            $this->assertLessThanOrEqual(self::MAX_MEAN_CHANNEL_DIFFERENCE, $this->meanChannelDifference($reference, $capture));
        } finally {
            @unlink($capture);
        }
    }

    public function test_approved_admin_component_reference_checksums_are_unchanged(): void
    {
        foreach (array_keys(self::COMPONENT_STATES) as $state) {
            $reference = $this->componentReferencePath($state);

            $this->assertFileExists($reference);
            $this->assertFileExists($reference.'.sha256');
            $this->assertSame(trim((string) file_get_contents($reference.'.sha256')), hash_file('sha256', $reference));
        }
    }

    public function test_current_admin_component_gallery_matches_all_approved_references(): void
    {
        foreach (self::COMPONENT_STATES as $state => $configuration) {
            $temporaryPath = tempnam(sys_get_temp_dir(), 'cataloghub-admin-components-');
            $this->assertIsString($temporaryPath);
            @unlink($temporaryPath);
            $capture = $temporaryPath.'.png';

            try {
                $this->captureGallery(
                    dirname(__DIR__, 2),
                    $capture,
                    $configuration['width'],
                    $configuration['height'],
                    '?mode=components&section='.$configuration['section'],
                );
                $this->preserveCapture($capture, "admin-components-{$state}.png");
                $this->assertSame(
                    [$configuration['width'], $configuration['height']],
                    array_slice(getimagesize($capture) ?: [], 0, 2),
                );
                $this->assertLessThanOrEqual(
                    self::MAX_MEAN_CHANNEL_DIFFERENCE,
                    $this->meanChannelDifference($this->componentReferencePath($state), $capture),
                    "Admin component gallery state [{$state}] differs from its approved reference.",
                );
            } finally {
                @unlink($capture);
            }
        }
    }

    public function test_admin_component_interactions_pass_in_a_real_browser(): void
    {
        $root = dirname(__DIR__, 2);
        $dom = tempnam(sys_get_temp_dir(), 'cataloghub-admin-components-dom-');
        $this->assertIsString($dom);
        $capture = tempnam(sys_get_temp_dir(), 'cataloghub-admin-components-dummy-');
        $this->assertIsString($capture);
        @unlink($capture);

        try {
            $this->captureGallery($root, $capture.'.png', 1280, 1000, '?mode=components&section=acceptance&acceptance=1', $dom);
            $renderedDom = (string) file_get_contents($dom);
            $this->assertStringContainsString('data-browser-acceptance="passed"', $renderedDom);
            $this->assertStringNotContainsString('data-browser-acceptance="failed"', $renderedDom);
        } finally {
            @unlink($dom);
            @unlink($capture.'.png');
        }
    }

    public function test_visual_comparison_still_detects_meaningful_region_changes(): void
    {
        $reference = tempnam(sys_get_temp_dir(), 'cataloghub-visual-reference-');
        $capture = tempnam(sys_get_temp_dir(), 'cataloghub-visual-capture-');
        $this->assertIsString($reference);
        $this->assertIsString($capture);
        $referenceImage = imagecreatetruecolor(100, 100);
        $captureImage = imagecreatetruecolor(100, 100);
        $this->assertInstanceOf(GdImage::class, $referenceImage);
        $this->assertInstanceOf(GdImage::class, $captureImage);
        imagefill($referenceImage, 0, 0, imagecolorallocate($referenceImage, 255, 255, 255));
        imagefill($captureImage, 0, 0, imagecolorallocate($captureImage, 255, 255, 255));
        imagefilledrectangle($captureImage, 0, 0, 49, 49, imagecolorallocate($captureImage, 15, 23, 42));
        imagepng($referenceImage, $reference);
        imagepng($captureImage, $capture);

        try {
            $this->assertGreaterThan(self::MAX_MEAN_CHANNEL_DIFFERENCE, $this->meanChannelDifference($reference, $capture));
        } finally {
            @unlink($reference);
            @unlink($capture);
        }
    }

    private function captureGallery(
        string $root,
        string $capture,
        int $width = 1440,
        int $height = 1200,
        string $query = '',
        ?string $dom = null,
    ): void {
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
            $browserArguments = [
                'node',
                $root.'/tests/Support/capture-chrome.mjs',
                $chrome,
                "http://127.0.0.1:{$port}/dev/component-gallery{$query}",
                $capture,
                (string) $width,
                (string) $height,
            ];

            if ($dom !== null) {
                $browserArguments[] = $dom;
            }

            $browser = proc_open($browserArguments, $descriptors, $browserPipes, $root);

            $this->assertIsResource($browser, 'Unable to start Google Chrome.');
            $this->assertSame(0, proc_close($browser), (string) file_get_contents($log));
            $this->assertFileExists($capture);
        } finally {
            proc_terminate($server);
            proc_close($server);
            @unlink($log);
        }
    }

    private function componentReferencePath(string $state): string
    {
        return dirname(__DIR__, 2).'/tests/Visual/baselines/admin-components-'.$state.'.png';
    }

    private function preserveCapture(string $capture, string $filename): void
    {
        $configuredDirectory = getenv('VISUAL_ARTIFACT_DIR');

        if (! is_string($configuredDirectory) || trim($configuredDirectory) === '') {
            return;
        }

        $directory = rtrim($configuredDirectory, DIRECTORY_SEPARATOR);

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new \RuntimeException("Unable to create visual artifact directory [{$directory}].");
        }

        if (! copy($capture, $directory.DIRECTORY_SEPARATOR.$filename)) {
            throw new \RuntimeException("Unable to preserve visual artifact [{$filename}].");
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
