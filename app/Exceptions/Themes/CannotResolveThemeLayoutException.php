<?php

namespace App\Exceptions\Themes;

use RuntimeException;

final class CannotResolveThemeLayoutException extends RuntimeException
{
    public static function unsupportedPageType(string $pageType): self
    {
        return new self("Public page type [{$pageType}] is not supported by the theme layout resolver.");
    }
}
