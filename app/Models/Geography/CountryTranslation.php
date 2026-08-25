<?php

declare(strict_types=1);

namespace App\Models\Geography;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $country_id
 * @property string $locale
 * @property string $name
 */
#[Fillable(['country_id', 'locale', 'name'])]
final class CountryTranslation extends Model
{
    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
