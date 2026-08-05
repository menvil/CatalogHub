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
    }

    /** @throws JsonException */
    public function test_public_layouts_opt_into_the_foundation_font_without_overriding_filament_fonts(): void
    {
        $root = dirname(__DIR__, 3);
        $typography = file_get_contents($root.'/resources/css/tokens/typography.css');
        $publicLayout = file_get_contents($root.'/resources/views/public/layouts/app.blade.php');
        $legacyPublicLayout = file_get_contents($root.'/resources/views/layouts/public.blade.php');
        $manifest = json_decode((string) file_get_contents($root.'/public/build/manifest.json'), true, flags: JSON_THROW_ON_ERROR);

        $this->assertIsString($typography);
        $this->assertIsString($publicLayout);
        $this->assertIsString($legacyPublicLayout);
        $this->assertStringContainsString('--font-foundation-sans:', $typography);
        $this->assertStringNotContainsString('--font-sans:', $typography);
        $this->assertStringContainsString('font-foundation-sans', $publicLayout);
        $this->assertStringContainsString('font-foundation-sans', $legacyPublicLayout);

        foreach (['resources/css/central-admin.css', 'resources/css/site-admin.css'] as $entryPoint) {
            $compiled = file_get_contents($root.'/public/build/'.$manifest[$entryPoint]['file']);

            $this->assertIsString($compiled);
            $this->assertStringNotContainsString('--font-sans:"Instrument Sans"', $compiled);
        }
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
