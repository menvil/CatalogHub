<?php

namespace App\Models;

use Database\Factories\FacetOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'facet_definition_id',
    'value',
    'label_override',
    'position',
    'is_active',
    'config_json',
])]
final class FacetOption extends Model
{
    /** @use HasFactory<FacetOptionFactory> */
    use HasFactory;

    protected static function newFactory(): FacetOptionFactory
    {
        return FacetOptionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
            'config_json' => 'array',
        ];
    }

    /** @return BelongsTo<FacetDefinition, $this> */
    public function facetDefinition(): BelongsTo
    {
        return $this->belongsTo(FacetDefinition::class);
    }
}
