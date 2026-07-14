<?php

namespace App\Exceptions\Themes;

use RuntimeException;
use Throwable;

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

    public static function invalidManifest(string $themeCode, Throwable $previous): self
    {
        return new self("Theme {$themeCode} does not have a valid manifest: {$previous->getMessage()}", previous: $previous);
    }

    public static function incompatibleBlock(string $themeCode, string $blockCode, Throwable $previous): self
    {
        return new self("Theme {$themeCode} is incompatible with enabled homepage block {$blockCode}: {$previous->getMessage()}", previous: $previous);
    }
}
