<?php

namespace App\Actions\Translations;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\Locale;
use App\Models\Translations\AttributeTranslation;
use App\Services\Translations\TranslationSourceHashService;

final readonly class SaveAttributeTranslationAction
{
    public function __construct(private TranslationSourceHashService $hashService) {}

    /** @param array<string, mixed> $data */
    public function handle(AttributeDefinition $attribute, Locale $locale, array $data): AttributeTranslation
    {
        return AttributeTranslation::query()->updateOrCreate(
            ['attribute_definition_id' => $attribute->id, 'locale' => $locale->code],
            [
                'locale_id' => $locale->id,
                'label' => $data['label'] ?? null,
                'short_label' => $data['short_label'] ?? null,
                'help_text' => $data['help_text'] ?? null,
                'status' => $data['status'] ?? TranslationStatus::HumanReviewed,
                'source_hash' => $this->hashService->forAttribute($attribute),
            ],
        );
    }
}
