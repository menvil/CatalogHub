<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Data\CentralCatalog\CentralBrandQualityReadModelData;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Services\CentralCatalog\CentralBrandQualityEvaluator;
use App\Services\Media\BrandLogoPresenter;

final readonly class CentralBrandQualityQuery
{
    public function __construct(
        private CentralBrandQualityEvaluator $evaluator,
        private CentralBrandMediaQuery $media,
        private BrandLogoPresenter $logos,
    ) {}

    public function forBrand(CentralBrand $brand): CentralBrandQualityReadModelData
    {
        $locales = Locale::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('position')
            ->orderBy('code')
            ->get();

        $translations = BrandTranslation::query()
            ->where('brand_id', $brand->getKey())
            ->whereIn('locale_id', $locales->modelKeys())
            ->get()
            ->keyBy(static fn (BrandTranslation $translation): int => (int) $translation->locale_id);

        $assignment = $this->media->primaryLogoAssignmentFor($brand);
        $logo = $this->logos->forDetail($assignment?->asset);

        return new CentralBrandQualityReadModelData(
            summary: $this->evaluator->evaluate(
                $brand,
                $locales,
                $translations,
                $assignment !== null,
                $logo->url !== null,
            ),
            logo: $logo,
        );
    }
}
