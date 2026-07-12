<?php

namespace App\Models\CentralCatalog;

use App\Enums\CentralProductStatus;
use Database\Factories\CentralProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;

/**
 * @property CentralProductStatus $status
 */
#[Fillable(['central_brand_id', 'central_category_id', 'name', 'model', 'slug', 'status'])]
final class CentralProduct extends Model
{
    /** @use HasFactory<CentralProductFactory> */
    use HasFactory;

    protected $table = 'central_products';

    private const MAX_GENERATED_SLUG_SAVE_ATTEMPTS = 3;

    protected static function newFactory(): CentralProductFactory
    {
        return CentralProductFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => CentralProductStatus::class,
        ];
    }

    public function save(array $options = []): bool
    {
        $shouldRetryGeneratedSlug = ! $this->exists && blank($this->getAttribute('slug'));

        for ($attempt = 1; $attempt <= self::MAX_GENERATED_SLUG_SAVE_ATTEMPTS; $attempt++) {
            try {
                return parent::save($options);
            } catch (QueryException $exception) {
                if (
                    ! $shouldRetryGeneratedSlug ||
                    ! $this->isSlugUniqueConstraintViolation($exception) ||
                    $attempt === self::MAX_GENERATED_SLUG_SAVE_ATTEMPTS
                ) {
                    throw $exception;
                }

                $this->setAttribute('slug', null);
            }
        }

        return false;
    }

    /**
     * @return BelongsTo<CentralBrand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(CentralBrand::class, 'central_brand_id');
    }

    /**
     * @return BelongsTo<CentralCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CentralCategory::class, 'central_category_id');
    }

    /**
     * @return HasMany<CentralProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(CentralProductVariant::class, 'central_product_id');
    }

    /**
     * @return HasMany<CentralProductAttributeValue, $this>
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(CentralProductAttributeValue::class, 'central_product_id');
    }

    private function isSlugUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $message = $exception->getMessage();

        return in_array($sqlState, ['23000', '23505'], strict: true)
            && str_contains($message, 'central_products')
            && str_contains($message, 'slug');
    }
}
