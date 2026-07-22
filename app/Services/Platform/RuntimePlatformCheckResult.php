<?php

declare(strict_types=1);

namespace App\Services\Platform;

final readonly class RuntimePlatformCheckResult
{
    /**
     * @param  list<string>  $failures
     */
    public function __construct(
        public bool $supported,
        public string $phpVersion,
        public string $databaseDriver,
        public ?string $postgresVersion,
        public array $failures,
    ) {}
}
