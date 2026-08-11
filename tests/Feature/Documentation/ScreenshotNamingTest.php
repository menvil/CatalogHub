<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use InvalidArgumentException;
use Tests\Support\ScreenshotNaming;
use Tests\TestCase;

final class ScreenshotNamingTest extends TestCase
{
    public function test_screen_state_and_viewport_have_a_stable_reference_path(): void
    {
        $this->assertSame(
            'tests/Visual/baselines/z-010__component-gallery__1440x1200.png',
            ScreenshotNaming::referencePath('Z-010', 'component-gallery', 1440, 1200),
        );
    }

    public function test_invalid_screen_state_and_dimensions_are_rejected(): void
    {
        foreach ([
            ['screen' => 'Z-011', 'state' => 'default', 'width' => 1, 'height' => 1],
            ['screen' => 'Z-001', 'state' => 'Default state', 'width' => 1, 'height' => 1],
            ['screen' => 'Z-001', 'state' => 'default', 'width' => 0, 'height' => 1],
        ] as $input) {
            try {
                ScreenshotNaming::referencePath($input['screen'], $input['state'], $input['width'], $input['height']);
                $this->fail('Expected invalid screenshot input to be rejected.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
