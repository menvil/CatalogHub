<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

final class ResourceNotFoundException extends DomainException
{
    public function status(): int
    {
        return 404;
    }

    public function publicMessage(): string
    {
        return 'The requested resource was not found.';
    }
}
