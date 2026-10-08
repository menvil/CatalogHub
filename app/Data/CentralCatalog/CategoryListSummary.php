<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

final readonly class CategoryListSummary
{
    public function __construct(public int $total, public int $active, public int $withSchema, public int $missingTranslations, public int $needsReview) {}

    public function activePercentage(): ?float
    {
        return $this->total === 0 ? null : round($this->active / $this->total * 100, 1);
    }
}
