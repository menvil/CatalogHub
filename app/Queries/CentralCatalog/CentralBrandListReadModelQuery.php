<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Data\CentralCatalog\BrandListFiltersData;
use App\Data\CentralCatalog\CentralBrandListReadModelData;
use App\Data\CentralCatalog\CentralBrandListRow;
use App\Data\CentralCatalog\CentralBrandListSummary;
use App\Data\CentralCatalog\CentralBrandQualityReadModelData;
use App\Enums\CentralBrandQualityState;
use App\Enums\CentralBrandStatus;
use App\Enums\MediaDeliveryState;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class CentralBrandListReadModelQuery
{
    public function __construct(
        private CentralBrandListQuery $brands,
        private CentralBrandQualityBatchQuery $quality,
        private CentralBrandCategoryCoverageQuery $categoryCoverage,
    ) {}

    public function paginate(BrandListFiltersData $filters, ?int $page = null): CentralBrandListReadModelData
    {
        $allBrands = CentralBrand::query()->orderBy('id')->get();
        $healthByBrandId = $this->quality->forBrands($allBrands);
        $eligibleBrandIds = $this->eligibleBrandIds($filters, $healthByBrandId);
        $paginator = $this->brands->paginate($filters, $page, $eligibleBrandIds);
        $pageBrands = $paginator->getCollection();
        $categoryCounts = $this->categoryCoverage->countsForBrands($pageBrands);

        $rows = $pageBrands->map(function (CentralBrand $brand) use ($healthByBrandId, $categoryCounts): CentralBrandListRow {
            $health = $healthByBrandId->get((int) $brand->getKey());
            if (! $health instanceof CentralBrandQualityReadModelData) {
                throw new \LogicException('Brand list health projection is missing.');
            }

            return new CentralBrandListRow(
                brand: $brand,
                categoryCount: (int) $categoryCounts->get((int) $brand->getKey(), 0),
                health: $health,
            );
        });

        /** @var LengthAwarePaginator<int, CentralBrandListRow> $rowPaginator */
        $rowPaginator = new LengthAwarePaginator(
            $rows,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            $paginator->getOptions(),
        );

        return new CentralBrandListReadModelData(
            brands: $rowPaginator,
            summary: new CentralBrandListSummary(
                total: $allBrands->count(),
                active: $allBrands->where('status', CentralBrandStatus::Active)->count(),
                withLogos: $healthByBrandId->filter(
                    static fn (CentralBrandQualityReadModelData $health): bool => $health->logo->state === MediaDeliveryState::Ready,
                )->count(),
                missingTranslations: $healthByBrandId->filter(
                    static fn (CentralBrandQualityReadModelData $health): bool => $health->translations->missing > 0,
                )->count(),
                needsAttention: $healthByBrandId->filter(
                    static fn (CentralBrandQualityReadModelData $health): bool => $health->summary->state === CentralBrandQualityState::NeedsAttention,
                )->count(),
            ),
            healthByBrandId: $healthByBrandId,
        );
    }

    /**
     * @param  Collection<int, CentralBrandQualityReadModelData>  $healthByBrandId
     * @return list<int>|null
     */
    private function eligibleBrandIds(BrandListFiltersData $filters, Collection $healthByBrandId): ?array
    {
        if ($filters->translation === null && $filters->quality === null) {
            return null;
        }

        return $healthByBrandId
            ->filter(function (CentralBrandQualityReadModelData $health) use ($filters): bool {
                $translationMatches = match ($filters->translation) {
                    null => true,
                    'complete' => $health->translations->total > 0
                        && $health->translations->missing === 0
                        && $health->translations->outdated === 0,
                    'missing' => $health->translations->missing > 0,
                    'outdated' => $health->translations->outdated > 0,
                    'needs_attention' => $health->translations->missing > 0 || $health->translations->outdated > 0,
                    default => false,
                };
                $qualityMatches = $filters->quality === null
                    || $health->summary->state->value === $filters->quality;

                return $translationMatches && $qualityMatches;
            })
            ->keys()
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }
}
