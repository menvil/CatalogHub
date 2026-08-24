<?php

declare(strict_types=1);

namespace Tests\Visual;

use PHPUnit\Framework\TestCase;

final class BrandMediaVisualTest extends TestCase
{
    public function test_ca_014_current_v1_references_exist(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['ca-014__empty__1440x1000', 'ca-014__logo-ready__1440x1000', 'ca-014__logo-ready__390x844'] as $name) {
            self::assertFileExists("{$root}/tests/Visual/baselines/{$name}.png");
        }
    }
}
