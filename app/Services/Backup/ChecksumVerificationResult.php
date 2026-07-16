<?php

namespace App\Services\Backup;

final readonly class ChecksumVerificationResult
{
    /** @param list<string> $issues */
    public function __construct(
        public int $checkedCount,
        public array $issues,
    ) {}

    public function hasIssues(): bool
    {
        return $this->issues !== [];
    }
}
