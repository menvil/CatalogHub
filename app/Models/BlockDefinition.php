<?php

namespace App\Models;

use App\Enums\BlockStatus;
use Database\Factories\BlockDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property BlockStatus $status
 * @property list<string> $supported_page_types_json
 * @property list<string>|null $required_features_json
 * @property array<string, mixed>|null $config_schema_json
 */
#[Fillable([
    'code',
    'name',
    'description',
    'category',
    'supported_page_types_json',
    'required_features_json',
    'config_schema_json',
    'view_component',
    'preview_image_path',
    'status',
])]
final class BlockDefinition extends Model
{
    /** @use HasFactory<BlockDefinitionFactory> */
    use HasFactory;

    protected $table = 'block_registry';

    protected static function newFactory(): BlockDefinitionFactory
    {
        return BlockDefinitionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'supported_page_types_json' => 'array',
            'required_features_json' => 'array',
            'config_schema_json' => 'array',
            'status' => BlockStatus::class,
        ];
    }

    /**
     * @param  Builder<BlockDefinition>  $query
     * @return Builder<BlockDefinition>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', BlockStatus::Active);
    }

    public function isActive(): bool
    {
        return $this->status === BlockStatus::Active;
    }

    public function supportsPage(string $pageType): bool
    {
        return in_array($pageType, $this->supported_page_types_json, true);
    }
}
