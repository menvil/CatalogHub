<?php

declare(strict_types=1);

namespace Tests\Unit\DesignSystem;

use PHPUnit\Framework\TestCase;

final class DesignSystemDocumentationTest extends TestCase
{
    public function test_design_system_index_links_every_foundation_contract(): void
    {
        $root = dirname(__DIR__, 3);
        $index = file_get_contents($root.'/docs/design-system/README.md');

        $this->assertIsString($index);

        foreach (['visual-primitives-audit.md', 'tokens.md', 'icons.md', 'responsive.md', 'admin-ui-tokens.md'] as $document) {
            $this->assertFileExists($root.'/docs/design-system/'.$document);
            $this->assertStringContainsString($document, $index);
        }
    }

    public function test_new_foundation_sources_do_not_introduce_raw_colors_or_arbitrary_geometry(): void
    {
        $root = dirname(__DIR__, 3);
        $rawColorNames = 'white|black|red|orange|amber|yellow|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|slate|gray|zinc|neutral|stone';
        $colorUtilities = 'accent|bg|border|caret|decoration|divide|fill|from|outline|placeholder|ring|shadow|stroke|text|to|via';
        $files = [
            'resources/css/foundation.css',
            'resources/css/tokens/colors.css',
            'resources/css/tokens/typography.css',
            'resources/css/tokens/geometry.css',
            'resources/css/tokens/responsive.css',
            'resources/views/central/component-gallery.blade.php',
            'resources/views/components/ui/icon.blade.php',
        ];

        foreach ($files as $file) {
            $source = file_get_contents($root.'/'.$file);

            $this->assertIsString($source);
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-f]{3,8}\b/i', $source, "Raw color found in [{$file}].");
            $this->assertDoesNotMatchRegularExpression('/\b(?:rgb|rgba|hsl|hsla)\s*\(/i', $source, "Raw color function found in [{$file}].");
            $this->assertDoesNotMatchRegularExpression("/(?:{$colorUtilities})-(?:{$rawColorNames})(?:-|\\b)/", $source, "Raw color utility found in [{$file}].");
            $this->assertDoesNotMatchRegularExpression("/\\b(?:color|background(?:-color)?|border(?:-(?:top|right|bottom|left))?(?:-color)?|outline(?:-color)?|text-decoration(?:-color)?|fill|stroke)\\s*:\\s*[^;{}]*\\b(?:{$rawColorNames})\\b/i", $source, "Raw named color found in [{$file}].");
            $this->assertDoesNotMatchRegularExpression('/[a-z][a-z0-9:-]*-\[[^\]]+\]/i', $source, "Arbitrary utility value found in [{$file}].");
        }
    }
}
