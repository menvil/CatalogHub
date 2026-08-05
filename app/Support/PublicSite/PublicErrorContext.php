<?php

declare(strict_types=1);

namespace App\Support\PublicSite;

final readonly class PublicErrorContext
{
    public function __construct(
        public string $siteName,
        public string $homeUrl,
    ) {}
}
