<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

final class AuthorizationDeniedException extends DomainException
{
    public function status(): int
    {
        return 403;
    }

    public function publicMessage(): string
    {
        return 'You are not authorized to perform this action.';
    }
}
