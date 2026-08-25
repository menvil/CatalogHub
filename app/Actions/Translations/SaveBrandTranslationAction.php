<?php

declare(strict_types=1);

namespace App\Actions\Translations;

use App\Data\Translations\BrandTranslationInput;
use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Translations\TranslationSourceHashService;
use App\Services\Translations\TranslationStatsService;
use Illuminate\Support\Facades\DB;

final readonly class SaveBrandTranslationAction
{
    private const MEANINGFUL_FIELDS = ['locale', 'name', 'tagline', 'short_description', 'description', 'seo_title', 'seo_description', 'status'];

    public function __construct(private TranslationSourceHashService $hashService, private AuditRecorder $audit) {}

    public function handle(User $actor, CentralBrand $brand, Locale $locale, BrandTranslationInput $input): BrandTranslation
    {
        $translation = DB::transaction(function () use ($actor, $brand, $locale, $input): BrandTranslation {
            CentralBrand::query()->lockForUpdate()->findOrFail($brand->id);
            $existing = BrandTranslation::query()->where('brand_id', $brand->id)->where('locale_id', $locale->id)->first();
            $values = [
                'locale' => $locale->code,
                'name' => $input->name,
                'tagline' => $input->tagline,
                'short_description' => $input->shortDescription,
                'description' => $input->description,
                'seo_title' => $input->seoTitle,
                'seo_description' => $input->seoDescription,
                'status' => $input->status->value,
                'source_hash' => $this->hashService->forBrand($brand),
            ];
            $beforeValues = null;

            if ($existing instanceof BrandTranslation) {
                $beforeValues = [];

                foreach (self::MEANINGFUL_FIELDS as $field) {
                    $beforeValues[$field] = $existing->getRawOriginal($field);
                }
            }
            $changedFields = $existing instanceof BrandTranslation
                ? array_keys(array_filter($values, static fn (mixed $value, string $field): bool => in_array($field, self::MEANINGFUL_FIELDS, true) && $beforeValues[$field] !== $value, ARRAY_FILTER_USE_BOTH))
                : self::MEANINGFUL_FIELDS;

            BrandTranslation::query()->upsert([['brand_id' => $brand->id, 'locale_id' => $locale->id, ...$values]], ['brand_id', 'locale_id'], array_keys($values));
            $saved = BrandTranslation::query()->where('brand_id', $brand->id)->where('locale_id', $locale->id)->firstOrFail();

            if ($changedFields !== []) {
                $before = $existing instanceof BrandTranslation ? ['translation_id' => $existing->id, 'locale' => $existing->locale, 'status' => $existing->getRawOriginal('status'), 'changed_fields' => $changedFields] : null;
                $this->audit->record(AuditAction::CatalogBrandTranslationSaved, AuditContext::Central, $actor, $brand, null, $before, ['translation_id' => $saved->id, 'locale' => $saved->locale, 'status' => $saved->getRawOriginal('status'), 'changed_fields' => $changedFields]);
            }

            return $saved;
        });

        TranslationStatsService::forgetDashboardCache();

        return $translation;
    }
}
