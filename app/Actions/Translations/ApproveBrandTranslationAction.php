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
use App\Services\Translations\TranslationSourceHashService;
use App\Services\Translations\TranslationStatsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ApproveBrandTranslationAction
{
    public function __construct(
        private ApproveTranslationAction $approve,
        private TranslationSourceHashService $sourceHashes,
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

            if ($oldStatus === TranslationStatus::Approved->value) {
                return [$translation, false];
            }

            $expectedSourceHash = $this->sourceHashes->forBrand($lockedBrand);

            if (! is_string($translation->source_hash) || ! hash_equals($expectedSourceHash, $translation->source_hash)) {
                throw ValidationException::withMessages([
                    'translation' => 'Save this translation against the current canonical source before approving it.',
                ]);
            }

            /** @var BrandTranslation $approved */
            $approved = $this->approve->handle($translation, $actor, false);
            $this->audit->record(
                AuditAction::TranslationApproved,
                AuditContext::Central,
                $actor,
                $lockedBrand,
                null,
                [
                    'translation_id' => $translation->getKey(),
                    'locale' => $translation->locale,
                    'status' => $oldStatus,
                    'changed_fields' => ['status', 'approval'],
                ],
                [
                    'translation_id' => $approved->getKey(),
                    'locale' => $approved->locale,
                    'status' => TranslationStatus::Approved->value,
                    'changed_fields' => ['status', 'approval'],
                ],
            );

            return [$approved, true];
        });

        if ($mutated) {
            TranslationStatsService::forgetDashboardCache();
        }

        return $translation;
    }
}
