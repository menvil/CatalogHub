<?php

namespace App\Queries\Media;

use App\Data\Media\MediaLibraryFiltersData;
use App\Models\MediaAsset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class MediaLibraryQuery
{
    /** @return LengthAwarePaginator<int, MediaAsset> */
    public function paginate(MediaLibraryFiltersData $filters): LengthAwarePaginator
    {
        return MediaAsset::query()
            ->with(['variants' => fn ($query) => $query->where('variant_type', 'thumbnail')->where('status', 'ready')])
            ->when($filters->status !== null, fn ($query) => $query->where('status', $filters->status))
            ->when($filters->type !== null, fn ($query) => $query->where('type', $filters->type))
            ->when($filters->search !== null, function ($query) use ($filters): void {
                $query->where(function ($query) use ($filters): void {
                    $query->where('original_filename', 'like', "%{$filters->search}%")
                        ->orWhere('checksum', 'like', "%{$filters->search}%");
                });
            })
            ->latest()
            ->paginate(24);
    }
}
