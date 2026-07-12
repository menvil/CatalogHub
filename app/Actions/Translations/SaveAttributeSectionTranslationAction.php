<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\Locale;
use App\Models\Translations\AttributeSectionTranslation;
use App\Services\Translations\TranslationSourceHashService;

final readonly class SaveAttributeSectionTranslationAction
{
    public function __construct(private TranslationSourceHashService $hashService) {}

    /** @param array<string, mixed> $data */
    public function handle(AttributeSection $section, Locale $locale, array $data): AttributeSectionTranslation
    {
        return AttributeSectionTranslation::query()->updateOrCreate(
            ['attribute_section_id' => $section->id, 'locale' => $locale->code],
            [
                'locale_id' => $locale->id,
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? TranslationStatus::HumanReviewed,
                'source_hash' => $this->hashService->forAttributeSection($section),
            ],
        );
    }
}
