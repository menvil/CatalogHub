<?php

namespace App\Services\Media;

final readonly class MediaIntegrityCheckResult
{
    /** @param list<string> $missingPaths */
    public function __construct(
        public int $assetCount,
        public int $checkedFileCount,
        public array $missingPaths,
    ) {}

    public function hasMissingFiles(): bool
    {
        return $this->missingPaths !== [];
    }
}
