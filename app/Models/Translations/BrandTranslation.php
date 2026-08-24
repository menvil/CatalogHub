<?php

namespace App\Models\Translations;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\User;
use Database\Factories\BrandTranslationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['brand_id', 'locale_id', 'locale', 'name', 'tagline', 'short_description', 'description', 'seo_title', 'seo_description', 'status'])]
final class BrandTranslation extends Model
{
    /** @use HasFactory<BrandTranslationFactory> */
    use HasFactory;

    protected static function newFactory(): BrandTranslationFactory
    {
        return BrandTranslationFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => TranslationStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CentralBrand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(CentralBrand::class, 'brand_id');
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
