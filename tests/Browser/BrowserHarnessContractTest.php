<?php

declare(strict_types=1);

namespace Tests\Browser;

use PHPUnit\Framework\TestCase;

final class BrowserHarnessContractTest extends TestCase
{
    public function test_playwright_harness_is_pinned_and_uses_event_driven_waits(): void
    {
        $root = dirname(__DIR__, 2);
        $package = json_decode((string) file_get_contents($root.'/package.json'), true, flags: JSON_THROW_ON_ERROR);
        $lock = json_decode((string) file_get_contents($root.'/package-lock.json'), true, flags: JSON_THROW_ON_ERROR);
        $spec = (string) file_get_contents(__DIR__.'/central-login.spec.mjs');

        self::assertSame('1.62.1', $package['devDependencies']['@playwright/test'] ?? null);
        self::assertSame('1.62.1', $lock['packages']['node_modules/@playwright/test']['version'] ?? null);
        self::assertStringContainsString('CATALOGHUB_BROWSER_PORT=8014', $package['scripts']['test:browser'] ?? '');
        self::assertStringContainsString('CATALOGHUB_BROWSER_PORT=8015', $package['scripts']['test:visual'] ?? '');
        self::assertStringContainsString("trace: 'retain-on-failure'", (string) file_get_contents($root.'/playwright.config.mjs'));
        self::assertStringNotContainsString('waitForTimeout', $spec);
        self::assertStringNotContainsString('setTimeout', $spec);
    }
}
