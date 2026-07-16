<?php

namespace App\Services\Pricing;

use App\Data\Pricing\OfferCoverageDashboardData;
use App\Models\Site;
use App\Queries\Pricing\OfferCoverageQuery;

final readonly class OfferCoverageDashboardBuilder
{
    public function __construct(
        private OfferCoverageQuery $coverage,
        private StalePriceWarningBuilder $stalePrices,
    ) {}

    public function build(Site $site): OfferCoverageDashboardData
    {
        $overall = $this->coverage->overall($site);
        $total = $overall['total'];
        $covered = $overall['covered'];
        $categories = array_map(fn (array $category): array => $category + [
            'percent' => $this->percent($category['covered'], $category['total']),
        ], $this->coverage->byCategory($site));
        $sources = array_map(fn (array $source): array => $source + [
            'percent' => $this->percent($source['covered'], $total),
        ], $this->coverage->bySource($site));
        $stale = $this->stalePrices->build($site);

        return new OfferCoverageDashboardData(
            totalVisibleProducts: $total,
            productsWithOffers: $covered,
            productsWithoutOffers: max(0, $total - $covered),
            coveragePercent: $this->percent($covered, $total),
            categoryCoverage: $categories,
            sourceCoverage: $sources,
            staleOffersCount: $stale->staleOffersCount,
            expiredOffersCount: $stale->expiredOffersCount,
        );
    }

    private function percent(int $covered, int $total): float
    {
        return $total === 0 ? 0.0 : round(($covered / $total) * 100, 2);
    }
}
