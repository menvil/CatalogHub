<?php

namespace App\Models;

use Database\Factories\SiteHomeBlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $config_json
 * @property array<string, mixed>|null $visibility_json
 */
#[Fillable(['site_id', 'block_code', 'position', 'enabled', 'config_json', 'visibility_json'])]
final class SiteHomeBlock extends Model
{
    /** @use HasFactory<SiteHomeBlockFactory> */
    use HasFactory;

    protected static function newFactory(): SiteHomeBlockFactory
    {
        return SiteHomeBlockFactory::new();
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'enabled' => 'boolean',
            'config_json' => 'array',
            'visibility_json' => 'array',
        ];
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return BelongsTo<BlockDefinition, $this> */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(BlockDefinition::class, 'block_code', 'code');
    }
}
