<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

use App\Enums\CentralBrandQualityState;

final readonly class CentralBrandQualitySummary
{
    /** @param list<CentralBrandQualityCheck> $checks */
    public function __construct(
        public CentralBrandQualityState $state,
        public int $score,
        public int $completedChecks,
        public int $totalChecks,
        public array $checks,
    ) {}

    /** @return list<CentralBrandQualityCheck> */
    public function issues(): array
    {
        return array_values(array_filter(
            $this->checks,
            static fn (CentralBrandQualityCheck $check): bool => ! $check->completed,
        ));
    }

    /** @return list<string> */
    public function issueCodes(): array
    {
        return array_map(
            static fn (CentralBrandQualityCheck $check): string => (string) $check->issueCode?->value,
            $this->issues(),
        );
    }
}
