<?php

declare(strict_types=1);

namespace App\Exceptions\Sites;

use RuntimeException;

final class InvalidSiteRuntimeConfigurationException extends RuntimeException
{
    public static function forSite(string $siteCode, string $reason): self
    {
        return new self("Invalid runtime configuration for site [{$siteCode}]: {$reason}");
    }
}
