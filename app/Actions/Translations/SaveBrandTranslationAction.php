<?php

declare(strict_types=1);

namespace App\Actions\Translations;

use App\Data\Translations\BrandTranslationInput;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Services\Translations\TranslationSourceHashService;
use App\Services\Translations\TranslationStatsService;

final readonly class SaveBrandTranslationAction
{
    public function __construct(private TranslationSourceHashService $hashService) {}

    public function handle(CentralBrand $brand, Locale $locale, BrandTranslationInput $input): BrandTranslation
    {
        $translation = BrandTranslation::query()->firstOrNew([
            'brand_id' => $brand->id,
            'locale' => $locale->code,
        ]);

        $translation->fill([
            'locale_id' => $locale->id,
            'name' => $input->name,
            'tagline' => $input->tagline,
            'short_description' => $input->shortDescription,
            'description' => $input->description,
            'seo_title' => $input->seoTitle,
            'seo_description' => $input->seoDescription,
            'status' => $input->status,
        ]);
        $translation->forceFill(['source_hash' => $this->hashService->forBrand($brand)]);
        $translation->save();

        TranslationStatsService::forgetDashboardCache();

        return $translation;
    }
}
