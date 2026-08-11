<?php

declare(strict_types=1);

namespace Tests\Unit\Foundation;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class UnitSuiteIsolationTest extends TestCase
{
    public function test_foundation_unit_baseline_does_not_boot_framework_database_or_network(): void
    {
        $forbidden = [
            'Tests'.'\\TestCase',
            'Illuminate'.'\\Foundation\\Testing',
            'Illuminate'.'\\Support\\Facades\\DB',
            'Http::',
            'curl_',
        ];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php' || $file->getPathname() === __FILE__) {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());

            foreach ($forbidden as $token) {
                self::assertStringNotContainsString(
                    $token,
                    $source,
                    "Foundation unit test [{$file->getFilename()}] uses forbidden runtime token [{$token}].",
                );
            }
        }
    }
}
