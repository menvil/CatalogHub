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

        foreach (['visual-primitives-audit.md', 'tokens.md', 'icons.md', 'responsive.md'] as $document) {
            $this->assertFileExists($root.'/docs/design-system/'.$document);
            $this->assertStringContainsString($document, $index);
        }
    }

    public function test_new_foundation_sources_do_not_introduce_raw_colors_or_arbitrary_geometry(): void
    {
        $root = dirname(__DIR__, 3);
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
            $this->assertDoesNotMatchRegularExpression('/(?:m|p|gap|w|h|min-w|max-w)-\[[^\]]+\]/', $source, "Arbitrary geometry found in [{$file}].");
        }
    }
}
