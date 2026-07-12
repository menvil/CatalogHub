<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\Translations\CategoryTranslation;
use App\Services\Translations\TranslationSourceHashService;

final readonly class SaveCategoryTranslationAction
{
    public function __construct(private TranslationSourceHashService $hashService) {}

    /** @param array<string, mixed> $data */
    public function handle(CentralCategory $category, Locale $locale, array $data): CategoryTranslation
    {
        $translation = CategoryTranslation::query()->updateOrCreate(
            ['category_id' => $category->id, 'locale' => $locale->code],
            [
                'locale_id' => $locale->id,
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'status' => $data['status'] ?? TranslationStatus::HumanReviewed,
            ],
        );

        $translation->forceFill(['source_hash' => $this->hashService->forCategory($category)])->save();

        return $translation;
    }
}
