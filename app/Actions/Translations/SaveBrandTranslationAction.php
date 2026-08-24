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
        BrandTranslation::query()->upsert([[
            'brand_id' => $brand->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'name' => $input->name,
            'tagline' => $input->tagline,
            'short_description' => $input->shortDescription,
            'description' => $input->description,
            'seo_title' => $input->seoTitle,
            'seo_description' => $input->seoDescription,
            'status' => $input->status->value,
            'source_hash' => $this->hashService->forBrand($brand),
        ]], ['brand_id', 'locale_id'], [
            'locale',
            'name',
            'tagline',
            'short_description',
            'description',
            'seo_title',
            'seo_description',
            'status',
            'source_hash',
        ]);

        $translation = BrandTranslation::query()
            ->where('brand_id', $brand->id)
            ->where('locale_id', $locale->id)
            ->firstOrFail();

        TranslationStatsService::forgetDashboardCache();

        return $translation;
    }
}
