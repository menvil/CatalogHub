<?php

declare(strict_types=1);

namespace Tests\Feature\DesignSystem;

use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class TokenIntegrationTest extends TestCase
{
    /** @throws JsonException */
    #[DataProvider('presentationEntryPointProvider')]
    public function test_each_presentation_bundle_contains_the_shared_semantic_foundation(string $entryPoint): void
    {
        $root = dirname(__DIR__, 3);
        $manifestPath = $root.'/public/build/manifest.json';

        $this->assertFileExists($manifestPath, 'Run the production frontend build before integration tests.');

        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey($entryPoint, $manifest);

        $compiledPath = $root.'/public/build/'.$manifest[$entryPoint]['file'];
        $this->assertFileExists($compiledPath);
        $compiled = file_get_contents($compiledPath);

        $this->assertIsString($compiled);
        $this->assertStringContainsString('--color-foundation-surface:', $compiled);
        $this->assertStringContainsString('--spacing-foundation-page:', $compiled);
        $this->assertStringContainsString('--font-sans:', $compiled);
    }

    /** @return array<string, array{string}> */
    public static function presentationEntryPointProvider(): array
    {
        return [
            'Central Admin' => ['resources/css/central-admin.css'],
            'Site Admin' => ['resources/css/site-admin.css'],
            'Public Site' => ['resources/css/public.css'],
        ];
    }
}
