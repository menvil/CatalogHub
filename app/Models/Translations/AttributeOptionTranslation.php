<?php

namespace App\Models\Translations;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\Locale;
use App\Models\User;
use Database\Factories\AttributeOptionTranslationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attribute_option_id', 'locale_id', 'locale', 'label', 'description', 'status'])]
final class AttributeOptionTranslation extends Model
{
    /** @use HasFactory<AttributeOptionTranslationFactory> */
    use HasFactory;

    protected static function newFactory(): AttributeOptionTranslationFactory
    {
        return AttributeOptionTranslationFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => TranslationStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AttributeOption, $this> */
    public function attributeOption(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class, 'attribute_option_id');
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
