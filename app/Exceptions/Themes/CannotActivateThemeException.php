<?php

namespace App\Exceptions\Themes;

use RuntimeException;

final class CannotActivateThemeException extends RuntimeException
{
    /** @param list<string> $missingFeatures */
    public static function incompatible(string $themeCode, array $missingFeatures): self
    {
        $missing = $missingFeatures === [] ? 'unknown capabilities' : implode(', ', $missingFeatures);

        return new self("Theme {$themeCode} is incompatible with enabled site features: {$missing}.");
    }

    public static function inactive(string $themeCode): self
    {
        return new self("Theme {$themeCode} is not active.");
    }
}
