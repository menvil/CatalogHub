<?php

declare(strict_types=1);

namespace Tests\Visual;

use App\Support\DesignSystem\PublicShellFixture;
use GdImage;
use PHPUnit\Framework\TestCase;

final class PublicShellVisualTest extends TestCase
{
    /**
     * Mobile tolerance accounts for Linux/macOS narrow-viewport font rasterization.
     * The high-contrast single layout needs a slightly wider platform allowance;
     * the browser test below independently enforces structure and asset isolation.
     *
     * @var array<string, array{width: int, height: int, tolerance: float}>
     */
    private const STATES = [
        'multi-desktop' => ['width' => 1280, 'height' => 900, 'tolerance' => 0.03],
        'multi-mobile' => ['width' => 360, 'height' => 800, 'tolerance' => 0.07],
        'single-desktop' => ['width' => 1280, 'height' => 900, 'tolerance' => 0.03],
        'single-mobile' => ['width' => 360, 'height' => 800, 'tolerance' => 0.12],
    ];

    public function test_approved_public_shell_reference_checksums_are_unchanged(): void
    {
        foreach (array_keys(self::STATES) as $state) {
            $reference = $this->referencePath($state);

            $this->assertFileExists($reference);
            $this->assertFileExists($reference.'.sha256');
            $this->assertSame(
                trim((string) file_get_contents($reference.'.sha256')),
                hash_file('sha256', $reference),
            );
        }
    }

    public function test_current_public_shells_match_all_approved_references(): void
    {
        $this->withServer(function (int $port, string $log): void {
            foreach (self::STATES as $state => $viewport) {
                $temporaryPath = tempnam(sys_get_temp_dir(), 'cataloghub-public-shell-');
                $this->assertIsString($temporaryPath);
                @unlink($temporaryPath);
                $capture = $temporaryPath.'.png';

                try {
                    $this->captureScreenshot($port, $log, $capture, $state, $viewport);
                    $this->assertSame(
                        [$viewport['width'], $viewport['height']],
                        array_slice(getimagesize($capture) ?: [], 0, 2),
                    );
                    $this->assertLessThanOrEqual(
                        $viewport['tolerance'],
                        $this->meanChannelDifference($this->referencePath($state), $capture),
                        "Public shell state [{$state}] differs from its approved reference.",
                    );
                } finally {
                    @unlink($capture);
                }
            }
        });
    }

    public function test_public_shells_have_no_admin_dependencies_or_browser_errors(): void
    {
        $this->withServer(function (int $port): void {
            foreach (self::STATES as $state => $viewport) {
                $dom = tempnam(sys_get_temp_dir(), 'cataloghub-public-shell-dom-');
                $browserLog = tempnam(sys_get_temp_dir(), 'cataloghub-public-shell-browser-');
                $this->assertIsString($dom);
                $this->assertIsString($browserLog);

                try {
                    $browser = proc_open([
                        $this->requiredChromeBinary(),
                        '--headless=new',
                        '--no-sandbox',
                        '--disable-gpu',
                        '--force-device-scale-factor=1',
                        "--window-size={$viewport['width']},{$viewport['height']}",
                        '--virtual-time-budget=2500',
                        '--dump-dom',
                        "http://127.0.0.1:{$port}/dev/public-shell?state={$state}&acceptance=1",
                    ], [
                        0 => ['file', $this->nullDevice(), 'r'],
                        1 => ['file', $dom, 'w'],
                        2 => ['file', $browserLog, 'a'],
                    ], $pipes, dirname(__DIR__, 2));

                    $this->assertIsResource($browser, 'Unable to start Google Chrome.');
                    $this->assertSame(0, proc_close($browser), (string) file_get_contents($browserLog));
                    $renderedDom = (string) file_get_contents($dom);
                    $this->assertStringContainsString(
                        'data-browser-acceptance="passed"',
                        $renderedDom,
                        "Browser acceptance failed for [{$state}]: ".$this->acceptanceFailureDetails($renderedDom),
                    );
                    $this->assertStringNotContainsString('/build/assets/central-admin-', $renderedDom);
                    $this->assertStringNotContainsString('/build/assets/site-admin-', $renderedDom);
                    $this->assertStringNotContainsString('data-central-shell=', $renderedDom);
                    $this->assertStringNotContainsString('data-site-shell=', $renderedDom);
                } finally {
                    @unlink($dom);
                    @unlink($browserLog);
                }
            }
        });
    }

    /** @param array{width: int, height: int, tolerance: float} $viewport */
    private function captureScreenshot(int $port, string $log, string $capture, string $state, array $viewport): void
    {
        $browser = proc_open([
            $this->requiredChromeBinary(),
            '--headless=new',
            '--no-sandbox',
            '--disable-gpu',
            '--hide-scrollbars',
            '--run-all-compositor-stages-before-draw',
            '--force-device-scale-factor=1',
            "--window-size={$viewport['width']},{$viewport['height']}",
            '--virtual-time-budget=2000',
            "--screenshot={$capture}",
            "http://127.0.0.1:{$port}/dev/public-shell?state={$state}",
        ], [
            0 => ['file', $this->nullDevice(), 'r'],
            1 => ['file', $log, 'a'],
            2 => ['file', $log, 'a'],
        ], $pipes, dirname(__DIR__, 2));

        $this->assertIsResource($browser, 'Unable to start Google Chrome.');
        $this->assertSame(0, proc_close($browser), (string) file_get_contents($log));
        $this->assertFileExists($capture);
    }

    private function withServer(callable $callback): void
    {
        $root = dirname(__DIR__, 2);
        $log = tempnam(sys_get_temp_dir(), 'cataloghub-public-shell-server-');
        $this->assertIsString($log);
        $environment = getenv();
        $environment['APP_ENV'] = 'testing';
        $server = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:0', '-t', $root.'/public', $root.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],
            [
                0 => ['file', $this->nullDevice(), 'r'],
                1 => ['file', $log, 'a'],
                2 => ['file', $log, 'a'],
            ],
            $pipes,
            $root.'/public',
            $environment,
        );

        if (! is_resource($server)) {
            @unlink($log);
            $this->fail('Unable to start the deterministic Public shell server.');
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

        $this->fail('Google Chrome is required for deterministic Public shell acceptance.');
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
                $this->fail('Public shell server stopped before binding: '.$output);
            }

            usleep(100_000);
        }

        $this->fail('Public shell server did not report its assigned port: '.(string) file_get_contents($log));
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
                fwrite($connection, "GET /dev/public-shell HTTP/1.1\r\nHost: 127.0.0.1\r\nConnection: close\r\n\r\n");
                stream_set_timeout($connection, 1);
                $response = stream_get_contents($connection);
                fclose($connection);

                if (is_string($response) && str_contains($response, 'data-public-shell-fixture="'.PublicShellFixture::VERSION.'"')) {
                    return;
                }
            }

            usleep(100_000);
        }

        $this->fail('The assigned server did not render the Public shell: '.(string) file_get_contents($log));
    }

    private function referencePath(string $state): string
    {
        $name = match ($state) {
            'multi-desktop' => 'z-005__default__1280x900',
            'single-desktop' => 'z-006__default__1280x900',
            default => "public-shell-{$state}",
        };

        return dirname(__DIR__, 2)."/tests/Visual/baselines/{$name}.png";
    }

    private function acceptanceFailureDetails(string $dom): string
    {
        if (preg_match('/data-browser-acceptance-failures="([^"]*)"/', $dom, $matches) !== 1) {
            return 'failure details were not recorded';
        }

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5) ?: 'no failure details were recorded';
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
