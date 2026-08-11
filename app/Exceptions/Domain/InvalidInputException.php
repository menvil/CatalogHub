<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

final class InvalidInputException extends DomainException
{
    public function status(): int
    {
        return 422;
    }

    public function publicMessage(): string
    {
        return 'The request is invalid.';
    }
}
