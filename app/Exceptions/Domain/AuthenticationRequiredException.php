<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

final class AuthenticationRequiredException extends DomainException
{
    public function status(): int
    {
        return 401;
    }

    public function publicMessage(): string
    {
        return 'Authentication is required.';
    }
}
