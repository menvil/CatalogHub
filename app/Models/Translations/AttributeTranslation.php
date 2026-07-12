<?php

namespace App\Models\Translations;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\Locale;
use App\Models\User;
use Database\Factories\AttributeTranslationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attribute_definition_id', 'locale_id', 'locale', 'label', 'short_label', 'help_text', 'status', 'source_hash', 'approved_at', 'approved_by_user_id'])]
final class AttributeTranslation extends Model
{
    /** @use HasFactory<AttributeTranslationFactory> */
    use HasFactory;

    protected static function newFactory(): AttributeTranslationFactory
    {
        return AttributeTranslationFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => TranslationStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AttributeDefinition, $this> */
    public function attributeDefinition(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class, 'attribute_definition_id');
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
