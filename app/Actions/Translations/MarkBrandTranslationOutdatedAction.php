<?php

declare(strict_types=1);

namespace App\Actions\Translations;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Translations\TranslationStatsService;
use Illuminate\Support\Facades\DB;

final readonly class MarkBrandTranslationOutdatedAction
{
    public function __construct(
        private MarkTranslationOutdatedAction $markOutdated,
        private AuditRecorder $audit,
    ) {}

    public function handle(User $actor, CentralBrand $brand, Locale $locale): BrandTranslation
    {
        [$translation, $mutated] = DB::transaction(function () use ($actor, $brand, $locale): array {
            $lockedBrand = CentralBrand::query()->lockForUpdate()->findOrFail($brand->getKey());
            $lockedLocale = Locale::query()->active()->lockForUpdate()->findOrFail($locale->getKey());
            $translation = BrandTranslation::query()
                ->where('brand_id', $lockedBrand->getKey())
                ->where('locale_id', $lockedLocale->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $oldStatus = $translation->getRawOriginal('status');

            if ($oldStatus === TranslationStatus::Outdated->value) {
                return [$translation, false];
            }

            /** @var BrandTranslation $outdated */
            $outdated = $this->markOutdated->handle($translation, false);
            $this->audit->record(
                AuditAction::TranslationMarkedOutdated,
                AuditContext::Central,
                $actor,
                $lockedBrand,
                null,
                [
                    'translation_id' => $translation->getKey(),
                    'locale' => $translation->locale,
                    'status' => $oldStatus,
                    'changed_fields' => ['status'],
                ],
                [
                    'translation_id' => $outdated->getKey(),
                    'locale' => $outdated->locale,
                    'status' => TranslationStatus::Outdated->value,
                    'changed_fields' => ['status'],
                ],
            );

            return [$outdated, true];
        });

        if ($mutated) {
            TranslationStatsService::forgetDashboardCache();
        }

        return $translation;
    }
}
