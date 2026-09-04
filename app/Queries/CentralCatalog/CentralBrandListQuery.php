<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Contracts\Persistence\StablePaginationBoundary;
use App\Data\CentralCatalog\BrandListFiltersData;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Support\Normalization\BrandInputNormalizer;
use App\Support\Normalization\OrganizationNameNormalizer;
use Illuminate\Pagination\LengthAwarePaginator;

final class CentralBrandListQuery implements StablePaginationBoundary
{
    /** @return LengthAwarePaginator<int, CentralBrand> */
    public function paginate(BrandListFiltersData $filters, ?int $page = null, ?array $eligibleBrandIds = null): LengthAwarePaginator
    {
        $sortColumn = match ($filters->sort) {
            'name' => 'normalized_name',
            'products' => 'products_count',
            default => $filters->sort,
        };

        return CentralBrand::query()
            ->with(['ownership.organization'])
            ->withCount(['products as products_count' => static fn ($query) => $query
                ->where('status', '!=', CentralProductStatus::Archived->value)])
            ->when($eligibleBrandIds !== null, fn ($query) => $query->whereIn('id', $eligibleBrandIds))
            ->when($filters->search !== null, function ($query) use ($filters): void {
                $normalizedSearch = BrandInputNormalizer::nameIdentity($filters->search);
                $slugSearch = mb_strtolower($filters->search, 'UTF-8');
                $organizationSearch = OrganizationNameNormalizer::search($filters->search);

                $query->where(function ($query) use ($normalizedSearch, $slugSearch, $organizationSearch): void {
                    $query->where('normalized_name', 'like', "%{$normalizedSearch}%")
                        ->orWhere('slug', 'like', "%{$slugSearch}%")
                        ->orWhereHas('ownership.organization', static fn ($query) => $query
                            ->where('normalized_name', 'like', "%{$organizationSearch}%"));
                });
            })
            ->when($filters->status !== null, fn ($query) => $query->where('status', $filters->status))
            ->when($filters->countryId !== null, fn ($query) => $query->where('country_id', $filters->countryId))
            ->when($filters->categoryCoverage === 'has', fn ($query) => $query->whereHas('products', static fn ($query) => $query
                ->where('status', '!=', CentralProductStatus::Archived->value)
                ->whereNotNull('central_category_id')))
            ->when($filters->categoryCoverage === 'none', fn ($query) => $query->whereDoesntHave('products', static fn ($query) => $query
                ->where('status', '!=', CentralProductStatus::Archived->value)
                ->whereNotNull('central_category_id')))
            ->orderBy($sortColumn, $filters->direction)
            ->orderBy('id')
            ->paginate($filters->perPage, ['*'], 'page', $page)
            ->withQueryString();
    }
}
