<?php

declare(strict_types=1);

namespace Tests\Unit\DesignSystem;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TokenContractTest extends TestCase
{
    /** @param list<string> $requiredTokens */
    #[DataProvider('tokenFileProvider')]
    public function test_foundation_token_files_define_the_required_contract(string $relativePath, array $requiredTokens): void
    {
        $path = dirname(__DIR__, 3).'/'.$relativePath;

        $this->assertFileExists($path);
        $css = file_get_contents($path);
        $this->assertIsString($css);

        foreach ($requiredTokens as $token) {
            $this->assertSame(1, substr_count($css, $token.':'), "Token [{$token}] must be defined exactly once.");
        }
    }

    public function test_raw_palette_values_are_unique_and_semantic_colors_do_not_embed_hex_values(): void
    {
        $root = dirname(__DIR__, 3);
        $palette = file_get_contents($root.'/resources/css/tokens/palette.css');
        $semantic = file_get_contents($root.'/resources/css/tokens/colors.css');

        $this->assertIsString($palette);
        $this->assertIsString($semantic);
        preg_match_all('/#(?:[0-9a-f]{6}|[0-9a-f]{3})\b/i', $palette, $matches);

        $this->assertNotEmpty($matches[0]);
        $this->assertSameSize($matches[0], array_unique(array_map('strtolower', $matches[0])));
        $this->assertDoesNotMatchRegularExpression('/#(?:[0-9a-f]{8}|[0-9a-f]{4})\b/i', $palette);
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-f]{3,8}\b/i', $semantic);
    }

    public function test_all_presentation_entry_points_import_the_shared_foundation(): void
    {
        $root = dirname(__DIR__, 3);

        foreach (['app.css', 'central-admin.css', 'site-admin.css', 'public.css'] as $entry) {
            $css = file_get_contents($root.'/resources/css/'.$entry);

            $this->assertIsString($css);
            $this->assertStringContainsString("@import './foundation.css';", $css, "Entry point [{$entry}] must import the shared foundation.");
        }
    }

    /** @return array<string, array{string, list<string>}> */
    public static function tokenFileProvider(): array
    {
        return [
            'colors' => ['resources/css/tokens/colors.css', [
                '--color-foundation-canvas',
                '--color-foundation-surface',
                '--color-foundation-surface-muted',
                '--color-foundation-border',
                '--color-foundation-text',
                '--color-foundation-text-muted',
                '--color-foundation-accent',
                '--color-foundation-accent-strong',
                '--color-foundation-accent-surface',
                '--color-foundation-focus',
                '--color-foundation-success',
                '--color-foundation-success-surface',
                '--color-foundation-warning',
                '--color-foundation-warning-surface',
                '--color-foundation-danger',
                '--color-foundation-danger-surface',
                '--color-foundation-info',
                '--color-foundation-info-surface',
                '--color-foundation-outdated',
                '--color-foundation-outdated-surface',
            ]],
            'typography' => ['resources/css/tokens/typography.css', [
                '--font-foundation-sans',
                '--font-foundation-mono',
                '--text-foundation-display',
                '--text-foundation-display--line-height',
                '--text-foundation-heading',
                '--text-foundation-heading--line-height',
                '--text-foundation-title',
                '--text-foundation-title--line-height',
                '--text-foundation-body',
                '--text-foundation-body--line-height',
                '--text-foundation-label',
                '--text-foundation-label--line-height',
                '--text-foundation-caption',
                '--text-foundation-caption--line-height',
                '--text-foundation-code',
                '--text-foundation-code--line-height',
            ]],
            'geometry' => ['resources/css/tokens/geometry.css', [
                '--spacing-foundation-page',
                '--spacing-foundation-card',
                '--spacing-foundation-section',
                '--spacing-foundation-field',
                '--radius-foundation-card',
                '--radius-foundation-control',
                '--radius-foundation-pill',
                '--radius-foundation-modal',
                '--shadow-foundation-card',
                '--shadow-foundation-floating',
                '--shadow-foundation-modal',
                '--width-foundation-sidebar',
                '--height-foundation-header',
                '--width-foundation-modal',
                '--width-foundation-table',
                '--width-foundation-content',
            ]],
            'responsive' => ['resources/css/tokens/responsive.css', [
                '--breakpoint-foundation-mobile',
                '--breakpoint-foundation-tablet',
                '--breakpoint-foundation-desktop',
                '--breakpoint-foundation-wide',
            ]],
        ];
    }
}
