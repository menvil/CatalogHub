<?php

declare(strict_types=1);

namespace Tests\Visual;

use GdImage;
use PHPUnit\Framework\TestCase;

final class CentralShellVisualTest extends TestCase
{
    /** @var array<string, array{width: int, height: int, query: string}> */
    private const STATES = [
        'default' => ['width' => 1280, 'height' => 900, 'query' => 'default'],
        'collapsed' => ['width' => 1280, 'height' => 900, 'query' => 'collapsed'],
        'mobile' => ['width' => 360, 'height' => 800, 'query' => 'mobile'],
        'long-header' => ['width' => 1280, 'height' => 900, 'query' => 'long-header'],
    ];

    public function test_approved_central_shell_reference_checksums_are_unchanged(): void
    {
        foreach (array_keys(self::STATES) as $state) {
            $reference = $this->referencePath($state);
            $checksum = $reference.'.sha256';

            $this->assertFileExists($reference);
            $this->assertFileExists($checksum);
            $this->assertSame(trim((string) file_get_contents($checksum)), hash_file('sha256', $reference));
        }
    }

    public function test_current_central_shell_matches_all_approved_references(): void
    {
        $this->withServer(function (int $port, string $log): void {
            foreach (self::STATES as $state => $configuration) {
                $temporaryPath = tempnam(sys_get_temp_dir(), 'cataloghub-central-shell-');
                $this->assertIsString($temporaryPath);
                @unlink($temporaryPath);
                $capture = $temporaryPath.'.png';

                try {
                    $this->captureScreenshot($port, $log, $capture, $configuration);
                    $this->assertSame(
                        [$configuration['width'], $configuration['height']],
                        array_slice(getimagesize($capture) ?: [], 0, 2),
                    );
                    $this->assertLessThanOrEqual(
                        0.03,
                        $this->meanChannelDifference($this->referencePath($state), $capture),
                        "Central shell state [{$state}] differs from its approved reference.",
                    );
                } finally {
                    @unlink($capture);
                }
            }
        });
    }

    public function test_navigation_interactions_pass_in_a_real_browser_without_runtime_errors(): void
    {
        $this->withServer(function (int $port, string $log): void {
            foreach (['default', 'mobile'] as $state) {
                $this->assertInteractionState($port, $log, $state, self::STATES[$state]);
            }
        });
    }

    /** @param array{width: int, height: int, query: string} $configuration */
    private function assertInteractionState(int $port, string $log, string $state, array $configuration): void
    {
        $dom = tempnam(sys_get_temp_dir(), 'cataloghub-central-shell-dom-');
        $this->assertIsString($dom);
        $browserLog = tempnam(sys_get_temp_dir(), 'cataloghub-central-shell-browser-');
        $this->assertIsString($browserLog);

        try {
            $descriptors = [
                0 => ['file', $this->nullDevice(), 'r'],
                1 => ['file', $dom, 'w'],
                2 => ['file', $browserLog, 'a'],
            ];
            $browser = proc_open([
                $this->requiredChromeBinary(),
                '--headless=new',
                '--no-sandbox',
                '--disable-gpu',
                '--force-device-scale-factor=1',
                "--window-size={$configuration['width']},{$configuration['height']}",
                '--virtual-time-budget=2500',
                '--dump-dom',
                "http://127.0.0.1:{$port}/dev/central-shell?state={$configuration['query']}&acceptance=1",
            ], $descriptors, $pipes, dirname(__DIR__, 2));

            $this->assertIsResource($browser, 'Unable to start Google Chrome.');
            $this->assertSame(0, proc_close($browser), (string) file_get_contents($browserLog));
            $renderedDom = (string) file_get_contents($dom);
            $this->assertStringContainsString('data-browser-acceptance="passed"', $renderedDom, "Acceptance failed for [{$state}].");
            $this->assertStringNotContainsString('data-browser-acceptance="failed"', $renderedDom);
            $this->assertStringContainsString('data-central-sidebar-collapsed="false"', $renderedDom);
            $this->assertStringContainsString('data-central-sidebar-mobile-open="false"', $renderedDom);
        } finally {
            @unlink($dom);
            @unlink($browserLog);
        }
    }

    /** @param array{width: int, height: int, query: string} $configuration */
    private function captureScreenshot(int $port, string $log, string $capture, array $configuration): void
    {
        $descriptors = [
            0 => ['file', $this->nullDevice(), 'r'],
            1 => ['file', $log, 'a'],
            2 => ['file', $log, 'a'],
        ];
        $browser = proc_open([
            $this->requiredChromeBinary(),
            '--headless=new',
            '--no-sandbox',
            '--disable-gpu',
            '--hide-scrollbars',
            '--run-all-compositor-stages-before-draw',
            '--force-device-scale-factor=1',
            "--window-size={$configuration['width']},{$configuration['height']}",
            '--virtual-time-budget=2000',
            "--screenshot={$capture}",
            "http://127.0.0.1:{$port}/dev/central-shell?state={$configuration['query']}",
        ], $descriptors, $pipes, dirname(__DIR__, 2));

        $this->assertIsResource($browser, 'Unable to start Google Chrome.');
        $this->assertSame(0, proc_close($browser), (string) file_get_contents($log));
        $this->assertFileExists($capture);
    }

    private function withServer(callable $callback): void
    {
        $root = dirname(__DIR__, 2);
        $log = tempnam(sys_get_temp_dir(), 'cataloghub-central-shell-server-');
        $this->assertIsString($log);
        $descriptors = [
            0 => ['file', $this->nullDevice(), 'r'],
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
            $this->fail('Unable to start the deterministic Central shell server.');
        }

        try {
            $port = $this->waitForServerPort($server, $log);
            $this->waitForShell($port, $log);
            $callback($port, $log);
        } finally {
            proc_terminate($server);
            proc_close($server);
            @unlink($log);
        }
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

        $this->fail('Google Chrome is required for deterministic Central shell acceptance.');
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
                $this->fail('Central shell server stopped before binding: '.$output);
            }

            usleep(100_000);
        }

        $this->fail('Central shell server did not report its assigned port: '.(string) file_get_contents($log));
    }

    private function waitForShell(int $port, string $log): void
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            set_error_handler(static fn (): bool => true);

            try {
                $connection = stream_socket_client("tcp://127.0.0.1:{$port}", $errorCode, $errorMessage, 0.1);
            } finally {
                restore_error_handler();
            }

            if (is_resource($connection)) {
                fwrite($connection, "GET /dev/central-shell HTTP/1.1\r\nHost: 127.0.0.1\r\nConnection: close\r\n\r\n");
                stream_set_timeout($connection, 1);
                $response = stream_get_contents($connection);
                fclose($connection);

                if (is_string($response) && str_contains($response, 'data-central-shell-fixture="central-shell-v1"')) {
                    return;
                }
            }

            usleep(100_000);
        }

        $this->fail('The assigned server did not render the Central shell: '.(string) file_get_contents($log));
    }

    private function referencePath(string $state): string
    {
        $name = $state === 'default' ? 'z-002__default__1280x900' : "central-shell-{$state}";

        return dirname(__DIR__, 2)."/tests/Visual/baselines/{$name}.png";
    }

    private function nullDevice(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
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
