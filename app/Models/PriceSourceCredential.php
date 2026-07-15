<?php

namespace App\Models;

use App\Enums\PriceSourceCredentialStatus;
use Database\Factories\PriceSourceCredentialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property PriceSourceCredentialStatus $status */
#[Fillable([
    'price_source_id', 'encrypted_credentials_json', 'status', 'last_rotated_at',
])]
#[Hidden(['encrypted_credentials_json'])]
final class PriceSourceCredential extends Model
{
    /** @use HasFactory<PriceSourceCredentialFactory> */
    use HasFactory;

    protected static function newFactory(): PriceSourceCredentialFactory
    {
        return PriceSourceCredentialFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => PriceSourceCredentialStatus::class,
            'last_rotated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PriceSource, $this> */
    public function priceSource(): BelongsTo
    {
        return $this->belongsTo(PriceSource::class);
    }
}
