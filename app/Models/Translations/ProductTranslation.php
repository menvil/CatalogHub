<?php

namespace App\Models\Translations;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\User;
use Database\Factories\ProductTranslationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'locale_id', 'locale', 'name', 'subtitle', 'short_description', 'description', 'seo_title', 'seo_description', 'status', 'source_hash', 'approved_at', 'approved_by_user_id'])]
final class ProductTranslation extends Model
{
    /** @use HasFactory<ProductTranslationFactory> */
    use HasFactory;

    protected static function newFactory(): ProductTranslationFactory
    {
        return ProductTranslationFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => TranslationStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CentralProduct, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(CentralProduct::class, 'product_id');
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
