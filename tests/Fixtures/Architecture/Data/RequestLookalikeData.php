<?php

declare(strict_types=1);

namespace Tests\Fixtures\Architecture\Data;

final class RequestLookalikeData
{
    public function description(): string
    {
        // request() belongs at the HTTP boundary.
        return 'The literal request() is not executable code.';
    }
}
