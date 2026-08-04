<?php

declare(strict_types=1);

namespace App\Exceptions\Sites;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UnknownSiteException extends NotFoundHttpException
{
    public static function forHost(string $host): self
    {
        return new self("No available site is configured for host [{$host}].");
    }
}
