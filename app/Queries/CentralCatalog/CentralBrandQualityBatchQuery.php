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
use Illuminate\Support\Collection;

final readonly class CentralBrandQualityBatchQuery
{
    public function __construct(
        private CentralBrandQualityEvaluator $evaluator,
        private CentralBrandMediaQuery $media,
        private BrandLogoPresenter $logos,
    ) {}

    /**
     * @param  Collection<int, CentralBrand>  $brands
     * @return Collection<int, CentralBrandQualityReadModelData>
     */
    public function forBrands(Collection $brands): Collection
    {
        $activeLocales = Locale::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('position')
            ->orderBy('code')
            ->get();
        $brandIds = $brands
            ->map(static fn (CentralBrand $brand): int => (int) $brand->getKey())
            ->values()
            ->all();
        $translations = $brandIds === [] || $activeLocales->isEmpty()
            ? collect()
            : BrandTranslation::query()
                ->whereIn('brand_id', $brandIds)
                ->whereIn('locale_id', $activeLocales->modelKeys())
                ->get()
                ->groupBy(static fn (BrandTranslation $translation): int => (int) $translation->brand_id)
                ->map(static fn (Collection $translations): Collection => $translations->keyBy(
                    static fn (BrandTranslation $translation): int => (int) $translation->locale_id,
                ));
        $assignments = $this->media->primaryLogoAssignmentsFor($brands);

        return $brands->mapWithKeys(function (CentralBrand $brand) use ($activeLocales, $translations, $assignments): array {
            /** @var Collection<int, BrandTranslation> $translationsByLocale */
            $translationsByLocale = $translations->get((int) $brand->getKey(), collect());
            $statusCounts = $translationsByLocale->countBy(static function (BrandTranslation $translation): string {
                $status = $translation->getAttribute('status');
                if (! $status instanceof TranslationStatus) {
                    throw new \LogicException('Brand translation status cast is not configured.');
                }

                return $status->value;
            });
            $translationSummary = new CentralBrandTranslationSummary(
                total: $activeLocales->count(),
                approved: (int) $statusCounts->get(TranslationStatus::Approved->value, 0),
                humanReviewed: (int) $statusCounts->get(TranslationStatus::HumanReviewed->value, 0),
                machineTranslated: (int) $statusCounts->get(TranslationStatus::MachineTranslated->value, 0),
                missing: (int) $statusCounts->get(TranslationStatus::Missing->value, 0)
                    + max(0, $activeLocales->count() - $translationsByLocale->count()),
                outdated: (int) $statusCounts->get(TranslationStatus::Outdated->value, 0),
            );
            $assignment = $assignments->get((int) $brand->getKey());
            $logo = $this->logos->forDetail($assignment?->asset);

            return [(int) $brand->getKey() => new CentralBrandQualityReadModelData(
                summary: $this->evaluator->evaluate(
                    $brand,
                    $activeLocales,
                    $translationsByLocale,
                    $assignment !== null,
                    $logo->url !== null,
                ),
                logo: $logo,
                translations: $translationSummary,
            )];
        });
    }
}
