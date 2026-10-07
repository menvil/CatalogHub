<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\Locale;
use App\Models\Translations\AttributeSectionTranslation;
use App\Services\Translations\TranslationLocaleIdentity;
use App\Services\Translations\TranslationSourceHashService;
use App\Services\Translations\TranslationStatsService;
use Illuminate\Support\Facades\DB;

final readonly class SaveAttributeSectionTranslationAction
{
    public function __construct(private TranslationSourceHashService $hashService) {}

    /** @param array<string, mixed> $data */
    public function handle(AttributeSection $section, Locale $locale, array $data): AttributeSectionTranslation
    {
        $translation = DB::transaction(function () use ($section, $locale, $data): AttributeSectionTranslation {
            $section = app(TranslationLocaleIdentity::class)->lockOwner($section);
            $locale = Locale::query()->lockForUpdate()->findOrFail($locale->id);

            $translation = AttributeSectionTranslation::query()->updateOrCreate(
                ['attribute_section_id' => $section->id, 'locale_id' => $locale->id],
                [
                    'locale' => $locale->code,
                    'name' => $data['name'] ?? null,
                    'description' => $data['description'] ?? null,
                    'status' => $data['status'] ?? TranslationStatus::HumanReviewed,
                ],
            );

            $translation->forceFill(['source_hash' => $this->hashService->forAttributeSection($section)])->save();

            return $translation;
        }, 3);

        TranslationStatsService::forgetDashboardCache();

        return $translation;
    }
}
