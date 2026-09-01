<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

final readonly class CentralBrandTranslationSummary
{
    public function __construct(
        public int $total,
        public int $approved,
        public int $humanReviewed,
        public int $machineTranslated,
        public int $missing,
        public int $outdated,
    ) {}

    public function complete(): int
    {
        return $this->approved + $this->humanReviewed + $this->machineTranslated;
    }

    public function score(): int
    {
        return $this->total === 0
            ? 100
            : (int) round(($this->complete() / $this->total) * 100);
    }
}
