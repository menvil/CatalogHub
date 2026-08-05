<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

final class IconUsageTest extends TestCase
{
    public function test_new_foundation_views_use_the_approved_heroicon_wrapper_without_emoji_or_inline_svg(): void
    {
        $root = dirname(__DIR__, 3);
        $icon = file_get_contents($root.'/resources/views/components/ui/icon.blade.php');
        $gallery = file_get_contents($root.'/resources/views/central/component-gallery.blade.php');
        $contract = file_get_contents($root.'/app/Support/DesignSystem/FoundationDesignSystem.php');

        $this->assertIsString($icon);
        $this->assertIsString($gallery);
        $this->assertIsString($contract);
        $this->assertStringContainsString('HEROICON_COMPONENTS', $icon);
        $this->assertStringContainsString('heroicon-o-', $contract);
        $this->assertStringContainsString('<x-ui.icon', $gallery);
        $this->assertStringNotContainsString('<svg', $gallery);
        $this->assertDoesNotMatchRegularExpression('/([\x{2600}-\x{27BF}]|[\x{1F1E6}-\x{1F1FF}]|[\x{1F300}-\x{1FAFF}])/u', $icon.$gallery.$contract);
    }
}
