<?php

declare(strict_types=1);

namespace Tests\Unit\DesignSystem;

use App\Support\DesignSystem\FoundationDesignSystem;
use PHPUnit\Framework\TestCase;

final class ResponsiveContractTest extends TestCase
{
    public function test_visual_viewports_and_density_rules_are_fixed(): void
    {
        $this->assertSame(['mobile', 'tablet', 'desktop', 'wide'], array_keys(FoundationDesignSystem::VIEWPORTS));
        $this->assertSame([360, 768, 1280, 1440], array_column(FoundationDesignSystem::VIEWPORTS, 'width'));
        $this->assertSame([800, 1024, 900, 1200], array_column(FoundationDesignSystem::VIEWPORTS, 'height'));
        $this->assertSame('comfortable', FoundationDesignSystem::VIEWPORTS['mobile']['density']);
        $this->assertSame('comfortable', FoundationDesignSystem::VIEWPORTS['tablet']['density']);
        $this->assertSame('compact', FoundationDesignSystem::VIEWPORTS['desktop']['density']);
        $this->assertSame('compact', FoundationDesignSystem::VIEWPORTS['wide']['density']);
    }
}
