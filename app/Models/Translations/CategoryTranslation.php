<?php

namespace App\Models\Translations;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\User;
use Database\Factories\CategoryTranslationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['category_id', 'locale_id', 'locale', 'name', 'description', 'seo_title', 'seo_description', 'status'])]
final class CategoryTranslation extends Model
{
    /** @use HasFactory<CategoryTranslationFactory> */
    use HasFactory;

    protected static function newFactory(): CategoryTranslationFactory
    {
        return CategoryTranslationFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => TranslationStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CentralCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CentralCategory::class, 'category_id');
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
