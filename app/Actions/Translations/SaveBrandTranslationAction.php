<?php

declare(strict_types=1);

namespace App\Actions\Translations;

use App\Data\Translations\BrandTranslationInput;
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

final readonly class SaveBrandTranslationAction
{
    private const MEANINGFUL_FIELDS = ['locale', 'name', 'tagline', 'short_description', 'description', 'seo_title', 'seo_description', 'status'];

    private const LOCALIZED_FIELDS = ['name', 'tagline', 'short_description', 'description', 'seo_title', 'seo_description'];

    public function __construct(private TranslationSourceHashService $hashService, private AuditRecorder $audit) {}

    public function handle(User $actor, CentralBrand $brand, Locale $locale, BrandTranslationInput $input): BrandTranslation
    {
        [$translation, $mutated] = DB::transaction(function () use ($actor, $brand, $locale, $input): array {
            $lockedBrand = CentralBrand::query()->lockForUpdate()->findOrFail($brand->id);
            $lockedLocale = Locale::query()->active()->lockForUpdate()->findOrFail($locale->id);
            $existing = BrandTranslation::query()
                ->where('brand_id', $lockedBrand->id)
                ->where('locale_id', $lockedLocale->id)
                ->lockForUpdate()
                ->first();
            $sourceHash = $this->hashService->forBrand($lockedBrand);
            $values = [
                'locale' => $lockedLocale->code,
                'name' => $input->name,
                'tagline' => $input->tagline,
                'short_description' => $input->shortDescription,
                'description' => $input->description,
                'seo_title' => $input->seoTitle,
                'seo_description' => $input->seoDescription,
                'status' => $input->status->value,
                'source_hash' => $sourceHash,
            ];
            $beforeValues = [];

            if ($existing instanceof BrandTranslation) {
                foreach (self::MEANINGFUL_FIELDS as $field) {
                    $beforeValues[$field] = $existing->getRawOriginal($field);
                }
            }

            if ($input->status === TranslationStatus::Approved
                && $existing?->getRawOriginal('status') !== TranslationStatus::Approved->value) {
                throw ValidationException::withMessages([
                    'status' => 'Use the explicit approval action to approve a translation.',
                ]);
            }

            $localizedContentChanged = $existing instanceof BrandTranslation && collect(self::LOCALIZED_FIELDS)
                ->contains(fn (string $field): bool => $beforeValues[$field] !== $values[$field]);
            $sourceChanged = $existing instanceof BrandTranslation && $existing->getRawOriginal('source_hash') !== $sourceHash;

            if ($existing?->getRawOriginal('status') === TranslationStatus::Approved->value
                && ($localizedContentChanged || $sourceChanged)
                && $input->status === TranslationStatus::Approved) {
                $values['status'] = TranslationStatus::HumanReviewed->value;
            }

            if ($values['status'] !== TranslationStatus::Approved->value) {
                $values['approved_at'] = null;
                $values['approved_by_user_id'] = null;
            }

            $changedFields = $existing instanceof BrandTranslation
                ? array_keys(array_filter(
                    $values,
                    static fn (mixed $value, string $field): bool => in_array($field, self::MEANINGFUL_FIELDS, true)
                        && $beforeValues[$field] !== $value,
                    ARRAY_FILTER_USE_BOTH,
                ))
                : array_values(array_filter(
                    self::MEANINGFUL_FIELDS,
                    static fn (string $field): bool => $field === 'locale' || $field === 'status' || $values[$field] !== null,
                ));

            if ($sourceChanged) {
                $changedFields[] = 'source_context';
            }

            if ($existing instanceof BrandTranslation) {
                $persistenceChanged = collect($values)->contains(
                    fn (mixed $value, string $field): bool => $existing->getRawOriginal($field) !== $value,
                );

                if (! $persistenceChanged) {
                    return [$existing, false];
                }

                $saved = $existing;
                $saved->forceFill($values)->saveOrFail();
            } else {
                $saved = new BrandTranslation;
                $saved->forceFill([
                    'brand_id' => $lockedBrand->id,
                    'locale_id' => $lockedLocale->id,
                    ...$values,
                ])->saveOrFail();
            }

            if ($changedFields !== []) {
                $before = $existing instanceof BrandTranslation ? ['translation_id' => $existing->id, 'locale' => $beforeValues['locale'], 'status' => $beforeValues['status'], 'changed_fields' => $changedFields] : null;
                $this->audit->record(AuditAction::CatalogBrandTranslationSaved, AuditContext::Central, $actor, $lockedBrand, null, $before, ['translation_id' => $saved->id, 'locale' => $saved->locale, 'status' => $saved->getRawOriginal('status'), 'changed_fields' => $changedFields]);
            }

            return [$saved, true];
        });

        if ($mutated) {
            TranslationStatsService::forgetDashboardCache();
        }

        return $translation;
    }
}
