<?php

namespace App\Models;

use App\Models\CentralCatalog\AttributeDefinition;
use Database\Factories\AttributeDisplayRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'attribute_definition_id',
    'market_code',
    'locale',
    'display_unit_id',
    'decimals',
    'rounding_mode',
    'suffix_style',
])]
final class AttributeDisplayRule extends Model
{
    /** @use HasFactory<AttributeDisplayRuleFactory> */
    use HasFactory;

    public const GLOBAL_MARKET_CODE = '__global';

    public const GLOBAL_LOCALE = '__global';

    protected static function newFactory(): AttributeDisplayRuleFactory
    {
        return AttributeDisplayRuleFactory::new();
    }

    protected function casts(): array
    {
        return [
            'decimals' => 'integer',
        ];
    }

    public function setMarketCodeAttribute(?string $value): void
    {
        $this->attributes['market_code'] = blank($value) ? self::GLOBAL_MARKET_CODE : $value;
    }

    public function setLocaleAttribute(?string $value): void
    {
        $this->attributes['locale'] = blank($value) ? self::GLOBAL_LOCALE : $value;
    }

    /**
     * @return BelongsTo<AttributeDefinition, $this>
     */
    public function attributeDefinition(): BelongsTo
    {
        return $this->belongsTo(AttributeDefinition::class, 'attribute_definition_id');
    }

    /**
     * @return BelongsTo<MeasurementUnit, $this>
     */
    public function displayUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'display_unit_id');
    }
}
