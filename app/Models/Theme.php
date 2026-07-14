<?php

namespace App\Models;

use App\Enums\ThemeStatus;
use Database\Factories\ThemeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property ThemeStatus $status
 * @property array<string, mixed>|null $config_json
 * @property-read ThemeManifestRecord|null $manifest
 */
#[Fillable([
    'code',
    'name',
    'description',
    'status',
    'version',
    'preview_image_path',
    'is_system',
    'config_json',
])]
final class Theme extends Model
{
    /** @use HasFactory<ThemeFactory> */
    use HasFactory;

    protected static function newFactory(): ThemeFactory
    {
        return ThemeFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => ThemeStatus::class,
            'is_system' => 'boolean',
            'config_json' => 'array',
        ];
    }

    /** @return HasOne<ThemeManifestRecord, $this> */
    public function manifest(): HasOne
    {
        return $this->hasOne(ThemeManifestRecord::class);
    }

    /** @return HasMany<LayoutTemplate, $this> */
    public function layoutTemplates(): HasMany
    {
        return $this->hasMany(LayoutTemplate::class);
    }

    /** @return HasMany<Site, $this> */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    /**
     * @param  Builder<Theme>  $query
     * @return Builder<Theme>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ThemeStatus::Active);
    }

    public function isActive(): bool
    {
        return $this->status === ThemeStatus::Active;
    }
}
