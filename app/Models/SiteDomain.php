<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SiteDomainType;
use Database\Factories\SiteDomainFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * @property SiteDomainType $type
 * @property bool $is_primary
 * @property bool $is_active
 */
#[Fillable(['site_id', 'host', 'type', 'is_primary', 'is_active'])]
final class SiteDomain extends Model
{
    /** @use HasFactory<SiteDomainFactory> */
    use HasFactory;

    protected static function newFactory(): SiteDomainFactory
    {
        return SiteDomainFactory::new();
    }

    protected function casts(): array
    {
        return [
            'type' => SiteDomainType::class,
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @param Builder<SiteDomain> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function setHostAttribute(string $host): void
    {
        $this->attributes['host'] = self::normalizeHost($host);
    }

    public static function normalizeHost(string $input): string
    {
        $input = trim($input);
        $parts = parse_url(str_contains($input, '://') ? $input : '//'.$input);
        $host = is_array($parts) ? ($parts['host'] ?? null) : null;

        if (! is_string($host) || $host === '') {
            throw new InvalidArgumentException('A valid site host is required.');
        }

        return strtolower(rtrim($host, '.'));
    }
}
