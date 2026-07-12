<?php

namespace App\Models\Imports;

use Database\Factories\ImportSourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'type',
    'status',
    'config_json',
    'description',
])]
final class ImportSource extends Model
{
    /** @use HasFactory<ImportSourceFactory> */
    use HasFactory;

    public const string TYPE_SERIALIZED_PHP = 'serialized_php';

    public const string TYPE_CSV = 'csv';

    public const string TYPE_JSON = 'json';

    public const string TYPE_API = 'api';

    public const string TYPE_SCRAPER = 'scraper';

    public const string TYPE_MERCHANT_FEED = 'merchant_feed';

    public const string TYPE_MANUAL_UPLOAD = 'manual_upload';

    protected static function newFactory(): ImportSourceFactory
    {
        return ImportSourceFactory::new();
    }

    protected function casts(): array
    {
        return [
            'config_json' => 'array',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isType(string $type): bool
    {
        return $this->type === $type;
    }

    public function isSerializedPhp(): bool
    {
        return $this->isType(self::TYPE_SERIALIZED_PHP);
    }

    /**
     * @return HasMany<ImportBatch, $this>
     */
    public function batches(): HasMany
    {
        return $this->hasMany(ImportBatch::class);
    }
}
