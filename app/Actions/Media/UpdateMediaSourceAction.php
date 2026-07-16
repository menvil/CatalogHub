<?php

namespace App\Actions\Media;

use App\Models\MediaAsset;
use App\Models\MediaSource;

final class UpdateMediaSourceAction
{
    /**
     * @param  array{source_type?: string|null, source_name?: string|null, source_url?: string|null, license_type?: string|null, license_url?: string|null, attribution?: string|null}  $data
     */
    public function handle(MediaAsset $asset, array $data): MediaSource
    {
        return MediaSource::query()->updateOrCreate(
            ['media_asset_id' => $asset->getKey()],
            $data + ['media_asset_id' => $asset->getKey()],
        );
    }
}
