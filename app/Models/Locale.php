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

    protected static function booted(): void
    {
        self::saving(function (Locale $locale): void {
            if (! $locale->is_default) {
                return;
            }

            self::withoutEvents(function () use ($locale): void {
                self::query()
                    ->when($locale->exists, fn (Builder $query): Builder => $query->whereKeyNot($locale->getKey()))
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            });
        });

        self::saved(function (Locale $locale): void {
            if (! $locale->is_default) {
                return;
            }

            self::withoutEvents(function () use ($locale): void {
                self::query()
                    ->whereKeyNot($locale->getKey())
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            });
        });
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
