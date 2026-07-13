<?php

namespace App\Models;

use App\Enums\MarketStatus;
use Database\Factories\MarketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property MarketStatus $status
 * @property array<string, mixed>|null $config_json
 */
#[Fillable([
    'code',
    'name',
    'country_code',
    'currency_code',
    'default_locale',
    'timezone',
    'status',
    'config_json',
])]
final class Market extends Model
{
    /** @use HasFactory<MarketFactory> */
    use HasFactory;

    protected static function newFactory(): MarketFactory
    {
        return MarketFactory::new();
    }

    protected function casts(): array
    {
        return [
            'config_json' => 'array',
            'status' => MarketStatus::class,
        ];
    }

    public function isActive(): bool
    {
        return $this->status === MarketStatus::Active;
    }

    public function isArchived(): bool
    {
        return $this->status === MarketStatus::Archived;
    }
}
