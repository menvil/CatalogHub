<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

final class ResourceConflictException extends DomainException
{
    public function status(): int
    {
        return 409;
    }

    public function publicMessage(): string
    {
        return 'The request conflicts with the current state.';
    }
}
