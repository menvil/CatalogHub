<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property array{value?: mixed} $value_json */
#[Fillable(['site_id', 'market_id', 'entity_type', 'entity_id', 'field', 'locale_code', 'value_json', 'reason', 'status'])]
final class SiteOverride extends Model
{
    protected function casts(): array
    {
        return ['entity_id' => 'integer', 'value_json' => 'array'];
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return BelongsTo<Market, $this> */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function overrideValue(): mixed
    {
        return $this->value_json['value'] ?? null;
    }
}
