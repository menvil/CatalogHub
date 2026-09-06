<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

final readonly class CentralBrandListSummary
{
    public function __construct(
        public int $total,
        public int $active,
        public int $withLogos,
        public int $missingTranslations,
        public int $needsAttention,
    ) {}

    public function percentage(int $count): ?float
    {
        return $this->total === 0
            ? null
            : round(($count / $this->total) * 100, 1);
    }
}
