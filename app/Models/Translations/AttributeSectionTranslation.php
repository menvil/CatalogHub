<?php

namespace App\Models\Translations;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\Locale;
use App\Models\User;
use Database\Factories\AttributeSectionTranslationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attribute_section_id', 'locale_id', 'locale', 'name', 'description', 'status', 'source_hash', 'approved_at', 'approved_by_user_id'])]
final class AttributeSectionTranslation extends Model
{
    /** @use HasFactory<AttributeSectionTranslationFactory> */
    use HasFactory;

    protected static function newFactory(): AttributeSectionTranslationFactory
    {
        return AttributeSectionTranslationFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => TranslationStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AttributeSection, $this> */
    public function attributeSection(): BelongsTo
    {
        return $this->belongsTo(AttributeSection::class, 'attribute_section_id');
    }

    /** @return BelongsTo<Locale, $this> */
    public function localeModel(): BelongsTo
    {
        return $this->belongsTo(Locale::class, 'locale_id');
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
