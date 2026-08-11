<?php

declare(strict_types=1);

namespace Tests\Support;

use InvalidArgumentException;

final class ScreenshotNaming
{
    public static function referencePath(string $screenId, string $state, int $width, int $height): string
    {
        if (preg_match('/\AZ-0(?:0[1-9]|10)\z/', $screenId) !== 1) {
            throw new InvalidArgumentException('Screen IDs must be Z-001 through Z-010.');
        }

        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $state) !== 1) {
            throw new InvalidArgumentException('Screenshot states must use lower-case kebab-case.');
        }

        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException('Screenshot dimensions must be positive.');
        }

        return sprintf('tests/Visual/baselines/%s__%s__%dx%d.png', strtolower($screenId), $state, $width, $height);
    }
}
