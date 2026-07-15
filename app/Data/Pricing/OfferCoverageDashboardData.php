<?php

namespace App\Data\Pricing;

final readonly class OfferCoverageDashboardData
{
    /**
     * @param  list<array{name: string, total: int, covered: int, percent: float}>  $categoryCoverage
     * @param  list<array{name: string, covered: int, percent: float}>  $sourceCoverage
     */
    public function __construct(
        public int $totalVisibleProducts,
        public int $productsWithOffers,
        public int $productsWithoutOffers,
        public float $coveragePercent,
        public array $categoryCoverage,
        public array $sourceCoverage,
        public int $staleOffersCount,
        public int $expiredOffersCount,
    ) {}
}
