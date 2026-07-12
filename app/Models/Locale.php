<?php

namespace App\Models;

use Database\Factories\LocaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code',
    'language_code',
    'region_code',
    'name',
    'native_name',
    'direction',
    'is_active',
    'is_default',
    'position',
])]
final class Locale extends Model
{
    /** @use HasFactory<LocaleFactory> */
    use HasFactory;

    protected static function newFactory(): LocaleFactory
    {
        return LocaleFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @param  Builder<Locale>  $query
     * @return Builder<Locale>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
