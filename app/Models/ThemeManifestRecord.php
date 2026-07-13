<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed> $manifest_json
 * @property list<string>|null $supports_json
 * @property array<string, string>|null $layouts_json
 */
#[Fillable([
    'theme_id',
    'manifest_json',
    'supports_json',
    'layouts_json',
    'schema_version',
    'validated_at',
    'validation_errors_json',
])]
final class ThemeManifestRecord extends Model
{
    protected $table = 'theme_manifests';

    protected function casts(): array
    {
        return [
            'manifest_json' => 'array',
            'supports_json' => 'array',
            'layouts_json' => 'array',
            'validated_at' => 'datetime',
            'validation_errors_json' => 'array',
        ];
    }

    /** @return BelongsTo<Theme, $this> */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
