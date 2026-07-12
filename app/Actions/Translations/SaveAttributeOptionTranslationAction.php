<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\Locale;
use App\Models\Translations\AttributeOptionTranslation;
use App\Services\Translations\TranslationSourceHashService;

final readonly class SaveAttributeOptionTranslationAction
{
    public function __construct(private TranslationSourceHashService $hashService) {}

    /** @param array<string, mixed> $data */
    public function handle(AttributeOption $option, Locale $locale, array $data): AttributeOptionTranslation
    {
        $translation = AttributeOptionTranslation::query()->updateOrCreate(
            ['attribute_option_id' => $option->id, 'locale' => $locale->code],
            [
                'locale_id' => $locale->id,
                'label' => $data['label'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? TranslationStatus::HumanReviewed,
            ],
        );

        $translation->forceFill(['source_hash' => $this->hashService->forAttributeOption($option)])->save();

        return $translation;
    }
}
