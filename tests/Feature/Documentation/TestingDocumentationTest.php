<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use PHPUnit\Framework\TestCase;

final class TestingDocumentationTest extends TestCase
{
    public function test_foundation_testing_docs_exist_and_relative_links_resolve(): void
    {
        $root = dirname(__DIR__, 3);
        $documents = [
            'docs/testing/README.md',
            'docs/testing/architecture-tests.md',
            'docs/testing/browser-tests.md',
            'docs/testing/test-matrix.md',
            'docs/testing/visual-tests.md',
        ];

        foreach ($documents as $document) {
            $path = $root.'/'.$document;
            self::assertFileExists($path);
            $markdown = (string) file_get_contents($path);

            preg_match_all('/\[[^]]+]\((?!https?:|#)([^)]+)\)/', $markdown, $matches);

            foreach ($matches[1] as $link) {
                self::assertFileExists(dirname($path).'/'.$link, "Broken documentation link [{$document} -> {$link}].");
            }
        }
    }

    public function test_matrix_maps_every_foundation_risk_and_contains_measured_results(): void
    {
        $root = dirname(__DIR__, 3);
        $matrix = (string) file_get_contents($root.'/docs/testing/test-matrix.md');

        foreach ([
            'Enum/default drift',
            'Invalid user/site/membership/domain/locale/audit graph',
            'Central login or disabled-account regression',
            'Cross-panel access or site-id tampering',
            'Unknown or alias host resolution',
            'Unsupported locale fallback',
            'Presentation context/import leakage',
            'Browser runtime/login integration failure',
            'Login screen visual drift',
            'Screenshot comparator false green',
        ] as $risk) {
            self::assertStringContainsString($risk, $matrix);
        }

        self::assertStringNotContainsString('pending final verification', $matrix);
        self::assertStringContainsString('Full PHP | passed', $matrix);
    }

    public function test_documented_commands_are_declared_by_the_package_manifests(): void
    {
        $root = dirname(__DIR__, 3);
        $composer = json_decode((string) file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $package = json_decode((string) file_get_contents($root.'/package.json'), true, flags: JSON_THROW_ON_ERROR);

        foreach (['test:unit', 'test:feature', 'test:architecture', 'test:browser', 'test:visual'] as $script) {
            self::assertArrayHasKey($script, $composer['scripts']);
        }

        foreach (['test:browser', 'test:browser:install', 'test:visual', 'test:visual:update'] as $script) {
            self::assertArrayHasKey($script, $package['scripts']);
        }
    }
}
