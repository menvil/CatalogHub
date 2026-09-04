<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Data\CentralCatalog\CentralBrandQualityReadModelData;
use App\Data\CentralCatalog\CentralBrandTranslationSummary;
use App\Enums\TranslationStatus;
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

        $statusCounts = $translations
            ->countBy(static function (BrandTranslation $translation): string {
                $status = $translation->getAttribute('status');
                if (! $status instanceof TranslationStatus) {
                    throw new \LogicException('Brand translation status cast is not configured.');
                }

                return $status->value;
            });
        $explicitMissing = (int) $statusCounts->get(TranslationStatus::Missing->value, 0);
        $absent = max(0, $locales->count() - $translations->count());

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
            translations: new CentralBrandTranslationSummary(
                total: $locales->count(),
                approved: (int) $statusCounts->get(TranslationStatus::Approved->value, 0),
                humanReviewed: (int) $statusCounts->get(TranslationStatus::HumanReviewed->value, 0),
                machineTranslated: (int) $statusCounts->get(TranslationStatus::MachineTranslated->value, 0),
                missing: $explicitMissing + $absent,
                outdated: (int) $statusCounts->get(TranslationStatus::Outdated->value, 0),
            ),
        );
    }
}
