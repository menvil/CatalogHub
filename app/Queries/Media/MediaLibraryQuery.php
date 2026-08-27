<?php

namespace App\Queries\Media;

use App\Contracts\Persistence\StablePaginationBoundary;
use App\Data\Media\MediaLibraryFiltersData;
use App\Models\MediaAsset;
use Illuminate\Pagination\LengthAwarePaginator;

final class MediaLibraryQuery implements StablePaginationBoundary
{
    /** @return LengthAwarePaginator<int, MediaAsset> */
    public function paginate(
        MediaLibraryFiltersData $filters,
        int $perPage = 24,
        ?int $page = null,
    ): LengthAwarePaginator {
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
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /** @return LengthAwarePaginator<int, MediaAsset> */
    public function paginateCompatibleImages(
        string $search,
        int $perPage = 6,
        int $page = 1,
        string $pageName = 'asset_page',
    ): LengthAwarePaginator {
        $allowedMimes = config('media.allowed_upload_mimes');

        return MediaAsset::query()
            ->with(['variants' => fn ($query) => $query
                ->whereIn('variant_type', ['brand_logo_128', 'brand_logo_256', 'brand_logo_512'])
                ->whereNull('locale')
                ->whereNull('site_id')
                ->whereNull('market_id')])
            ->where('type', 'image')
            ->where('status', 'active')
            ->whereIn('mime_type', is_array($allowedMimes) ? $allowedMimes : [])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('original_filename', 'like', "%{$search}%")
                        ->orWhere('checksum', 'like', "%{$search}%")
                        ->when(ctype_digit($search), fn ($query) => $query->orWhere('id', (int) $search));
                });
            })
            ->latest()
            ->latest('id')
            ->paginate($perPage, ['*'], $pageName, $page);
    }
}
