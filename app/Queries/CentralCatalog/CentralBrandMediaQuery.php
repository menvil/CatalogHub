<?php

namespace App\Queries\CentralCatalog;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Services\Media\MediaVariantProfile;
use App\Services\Media\MediaVariantSpecificationRegistry;
use Illuminate\Database\Eloquent\Builder;

final readonly class CentralBrandMediaQuery
{
    public function __construct(private MediaVariantSpecificationRegistry $variants) {}

    public function logoFor(CentralBrand $brand): ?MediaAsset
    {
        return $this->primaryLogoAssignmentFor($brand)?->asset;
    }

    public function primaryLogoAssignmentFor(CentralBrand $brand): ?MediaAssignment
    {
        return $this->canonicalPrimaryLogoQuery($brand)
            ->with(['asset.variants' => function ($query): void {
                $query
                    ->whereIn('variant_type', $this->brandLogoVariantNames())
                    ->whereNull('locale')
                    ->whereNull('site_id')
                    ->whereNull('market_id');
            }])
            ->orderBy('position')
            ->orderBy('id')
            ->first();
    }

    /** @return Builder<MediaAssignment> */
    public function primaryLogoContextQuery(CentralBrand $brand): Builder
    {
        return $this->baseLogoAssignmentQuery($brand)
            ->where('is_primary', true);
    }

    /** @return Builder<MediaAssignment> */
    public function canonicalPrimaryLogoQuery(CentralBrand $brand): Builder
    {
        return $this->primaryLogoContextQuery($brand)
            ->where('visibility', 'global');
    }

    /** @return Builder<MediaAssignment> */
    private function baseLogoAssignmentQuery(CentralBrand $brand): Builder
    {
        return MediaAssignment::query()
            ->forEntity(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, (int) $brand->getKey())
            ->forRole(MediaAssignment::ROLE_BRAND_LOGO)
            ->whereNull('locale')
            ->whereNull('site_id')
            ->whereNull('market_id');
    }

    /** @return list<string> */
    private function brandLogoVariantNames(): array
    {
        return array_map(
            static fn ($specification): string => $specification->name,
            $this->variants->forProfile(MediaVariantProfile::BrandLogo),
        );
    }
}
