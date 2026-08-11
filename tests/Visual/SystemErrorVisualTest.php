<?php

declare(strict_types=1);

namespace Tests\Visual;

use PHPUnit\Framework\TestCase;
use Tests\Visual\Concerns\InteractsWithVisualReferences;

final class SystemErrorVisualTest extends TestCase
{
    use InteractsWithVisualReferences;

    // System pages deliberately use inline system fonts so they render during an asset outage.
    private const MAX_MEAN_CHANNEL_DIFFERENCE = 0.07;

    /** @var array<string, array{width: int, height: int}> */
    private const STATES = [
        'system-error-desktop' => ['width' => 1280, 'height' => 900],
        'system-error-mobile' => ['width' => 360, 'height' => 800],
    ];

    public function test_approved_system_error_references_are_unchanged(): void
    {
        foreach (array_keys(self::STATES) as $state) {
            $reference = $this->referencePath($state);

            $this->assertFileExists($reference);
            $this->assertFileExists($reference.'.sha256');
            $this->assertSame(trim((string) file_get_contents($reference.'.sha256')), hash_file('sha256', $reference));
        }
    }

    public function test_deterministic_system_error_fixture_matches_approved_references(): void
    {
        $root = dirname(__DIR__, 2);
        $log = tempnam(sys_get_temp_dir(), 'cataloghub-system-error-server-');
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

        $this->assertIsResource($server, 'Unable to start the system-error visual server.');

        try {
            $port = $this->waitForServerPort($server, $log);

            foreach (self::STATES as $state => $configuration) {
                $temporaryPath = tempnam(sys_get_temp_dir(), 'cataloghub-system-error-');
                $this->assertIsString($temporaryPath);
                @unlink($temporaryPath);
                $capture = $temporaryPath.'.png';

                try {
                    $this->capture($port, $capture, $configuration);
                    $this->assertSame([$configuration['width'], $configuration['height']], array_slice(getimagesize($capture) ?: [], 0, 2));
                    $this->assertLessThanOrEqual(self::MAX_MEAN_CHANNEL_DIFFERENCE, $this->meanChannelDifference($this->referencePath($state), $capture));
                } finally {
                    @unlink($capture);
                }
            }
        } finally {
            proc_terminate($server);
            proc_close($server);
            @unlink($log);
        }
    }

    /** @param array{width: int, height: int} $configuration */
    private function capture(int $port, string $capture, array $configuration): void
    {
        $browser = proc_open([
            'node',
            dirname(__DIR__, 2).'/tests/Support/capture-chrome.mjs',
            $this->requiredChromeBinary(),
            "http://127.0.0.1:{$port}/dev/system-error",
            $capture,
            (string) $configuration['width'],
            (string) $configuration['height'],
        ], $this->descriptors(), $pipes, dirname(__DIR__, 2));

        $this->assertIsResource($browser, 'Unable to start Google Chrome.');
        $this->assertSame(0, proc_close($browser));
        $this->assertFileExists($capture);
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
                $this->fail('System-error visual server stopped before binding: '.$output);
            }

            usleep(100_000);
        }

        $this->fail('System-error visual server did not report its assigned port.');
    }
}
