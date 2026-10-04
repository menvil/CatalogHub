<?php

namespace App\Models\CentralCatalog;

use Illuminate\Database\Eloquent\Model;

/** @property int $revision */
final class CategoryHierarchyScope extends Model
{
    protected $table = 'category_hierarchy_scopes';

    protected $primaryKey = 'scope_key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['revision' => 'integer'];
    }

    public static function keyFor(?int $parentId): string
    {
        return $parentId === null ? 'root' : 'parent:'.$parentId;
    }
}
