<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\Translations\CategoryTranslation;
use App\Services\Translations\TranslationLocaleIdentity;
use App\Services\Translations\TranslationSourceHashService;
use App\Services\Translations\TranslationStatsService;
use Illuminate\Support\Facades\DB;

final readonly class SaveCategoryTranslationAction
{
    public function __construct(private TranslationSourceHashService $hashService) {}

    /** @param array<string, mixed> $data */
    public function handle(CentralCategory $category, Locale $locale, array $data): CategoryTranslation
    {
        $translation = DB::transaction(function () use ($category, $locale, $data): CategoryTranslation {
            $category = app(TranslationLocaleIdentity::class)->lockOwner($category);
            $locale = Locale::query()->lockForUpdate()->findOrFail($locale->id);

            $translation = CategoryTranslation::query()->firstOrNew([
                'category_id' => $category->id,
                'locale_id' => $locale->id,
            ]);

            $translation->fill([
                'locale' => $locale->code,
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'status' => $data['status'] ?? TranslationStatus::HumanReviewed,
            ]);
            $translation->forceFill(['source_hash' => $this->hashService->forCategory($category)]);
            $translation->save();

            return $translation;
        }, 3);

        TranslationStatsService::forgetDashboardCache();

        return $translation;
    }
}
