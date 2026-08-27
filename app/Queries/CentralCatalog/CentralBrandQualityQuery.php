<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Data\CentralCatalog\CentralBrandQualityReadModelData;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\MediaAssignment;
use App\Models\Translations\BrandTranslation;
use App\Services\CentralCatalog\CentralBrandQualityEvaluator;
use App\Services\Media\BrandLogoPresenter;

final readonly class CentralBrandQualityQuery
{
    public function __construct(
        private CentralBrandQualityEvaluator $evaluator,
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

        $assignment = MediaAssignment::query()
            ->with('asset.variants')
            ->forEntity(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, (int) $brand->getKey())
            ->forRole(MediaAssignment::ROLE_BRAND_LOGO)
            ->whereNull('locale')
            ->whereNull('site_id')
            ->whereNull('market_id')
            ->where('is_primary', true)
            ->orderBy('position')
            ->orderBy('id')
            ->first();
        $logo = $this->logos->forDetail($assignment?->asset);

        return new CentralBrandQualityReadModelData(
            summary: $this->evaluator->evaluate(
                $brand,
                $locales,
                $translations,
                $assignment instanceof MediaAssignment,
                $logo->url !== null,
            ),
            logo: $logo,
        );
    }
}
