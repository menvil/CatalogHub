<?php

namespace App\Services\Translations;

use App\Models\Translations\AttributeOptionTranslation;
use App\Models\Translations\AttributeSectionTranslation;
use App\Models\Translations\AttributeTranslation;
use App\Models\Translations\CategoryTranslation;
use App\Models\Translations\ProductTranslation;
use App\Models\Translations\UnitTranslation;
use App\Services\AttributeGlobalization\AttributeIdentityLock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class TranslationLocaleIdentity
{
    /** @template T of Model
     * @param  T  $owner
     * @return T
     */
    public function lockOwner(Model $owner): Model
    {
        app(AttributeIdentityLock::class)->acquireTarget();

        return $owner::query()->whereKey($owner->getKey())->lockForUpdate()->firstOrFail();
    }

    public function assertUnambiguous(string $table, string $ownerColumn, int $ownerId, int $localeId): void
    {
        if ((match ($table) {
            'product_translations' => ProductTranslation::class, 'category_translations' => CategoryTranslation::class, 'attribute_translations' => AttributeTranslation::class, 'attribute_section_translations' => AttributeSectionTranslation::class, 'attribute_option_translations' => AttributeOptionTranslation::class, 'unit_translations' => UnitTranslation::class, default => throw new \InvalidArgumentException('Unsupported typed translation owner.')
        })::query()->where($ownerColumn, $ownerId)->where('locale_id', $localeId)->count() > 1) {
            throw ValidationException::withMessages(['locale_id' => 'Explicit Locale identity reconciliation required; no translation winner can be selected.']);
        }
    }
}
