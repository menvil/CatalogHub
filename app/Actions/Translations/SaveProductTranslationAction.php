<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\Translations\ProductTranslation;
use App\Services\Translations\TranslationSourceHashService;
use App\Services\Translations\TranslationStatsService;

final readonly class SaveProductTranslationAction
{
    public function __construct(private TranslationSourceHashService $hashService) {}

    /** @param array<string, mixed> $data */
    public function handle(CentralProduct $product, Locale $locale, array $data): ProductTranslation
    {
        $translation = ProductTranslation::query()->updateOrCreate(
            ['product_id' => $product->id, 'locale' => $locale->code],
            [
                'locale_id' => $locale->id,
                'name' => $data['name'] ?? null,
                'subtitle' => $data['subtitle'] ?? null,
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null,
                'seo_title' => $data['seo_title'] ?? null,
                'seo_description' => $data['seo_description'] ?? null,
                'status' => $data['status'] ?? TranslationStatus::HumanReviewed,
            ],
        );

        $translation->forceFill(['source_hash' => $this->hashService->forProduct($product)])->save();
        TranslationStatsService::forgetDashboardCache();

        return $translation;
    }
}
