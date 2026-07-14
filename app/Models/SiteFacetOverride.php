<?php

namespace App\Models;

use Database\Factories\SiteFacetOverrideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'site_id',
    'facet_definition_id',
    'label_override',
    'position_override',
    'is_visible',
    'default_collapsed',
    'config_json',
])]
final class SiteFacetOverride extends Model
{
    /** @use HasFactory<SiteFacetOverrideFactory> */
    use HasFactory;

    protected static function newFactory(): SiteFacetOverrideFactory
    {
        return SiteFacetOverrideFactory::new();
    }

    protected function casts(): array
    {
        return [
            'position_override' => 'integer',
            'is_visible' => 'boolean',
            'default_collapsed' => 'boolean',
            'config_json' => 'array',
        ];
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return BelongsTo<FacetDefinition, $this> */
    public function facetDefinition(): BelongsTo
    {
        return $this->belongsTo(FacetDefinition::class);
    }
}
