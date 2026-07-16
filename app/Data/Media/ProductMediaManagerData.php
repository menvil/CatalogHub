<?php

namespace App\Data\Media;

use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final readonly class ProductMediaManagerData
{
    /**
     * @param  Collection<int|string, EloquentCollection<int, MediaAssignment>>  $assignments
     * @param  EloquentCollection<int, MediaAsset>  $assets
     */
    public function __construct(
        public Collection $assignments,
        public EloquentCollection $assets,
        public string $assetSearch,
    ) {}
}
