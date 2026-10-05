<?php

namespace App\Services\Translations;

use App\Models\Translations\AttributeOptionTranslation;
use App\Models\Translations\AttributeSectionTranslation;
use App\Models\Translations\AttributeTranslation;
use App\Models\Translations\CategoryTranslation;
use App\Models\Translations\UnitTranslation;
use Illuminate\Validation\ValidationException;

final class TranslationLocaleIdentity
{
    public function assertUnambiguous(string $table, string $ownerColumn, int $ownerId, int $localeId): void
    {
        if ((match ($table) {
            'category_translations' => CategoryTranslation::class, 'attribute_translations' => AttributeTranslation::class, 'attribute_section_translations' => AttributeSectionTranslation::class, 'attribute_option_translations' => AttributeOptionTranslation::class, 'unit_translations' => UnitTranslation::class, default => throw new \InvalidArgumentException('Unsupported typed translation owner.')
        })::query()->where($ownerColumn, $ownerId)->where('locale_id', $localeId)->count() > 1) {
            throw ValidationException::withMessages(['locale_id' => 'Explicit Locale identity reconciliation required; no translation winner can be selected.']);
        }
    }
}
