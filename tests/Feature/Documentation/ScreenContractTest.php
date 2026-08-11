<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use Tests\TestCase;

final class ScreenContractTest extends TestCase
{
    public function test_all_foundation_screen_contracts_and_visual_references_validate(): void
    {
        $output = [];
        $exitCode = 0;

        exec(PHP_BINARY.' '.escapeshellarg(base_path('scripts/validate-screen-contracts.php')).' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, implode(PHP_EOL, $output));
    }
}
