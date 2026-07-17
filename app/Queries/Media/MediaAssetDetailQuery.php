<?php

namespace App\Queries\Media;

use App\Models\MediaAsset;

final class MediaAssetDetailQuery
{
    public function get(MediaAsset $asset): MediaAsset
    {
        return $asset->load(['sources', 'variants']);
    }
}
