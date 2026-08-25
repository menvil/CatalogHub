<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralProduct;
use Carbon\CarbonImmutable;
use RuntimeException;

final class BrandDetailFixture
{
    public const VERSION = 'brand-detail-v2';

    public const ACTIVE_BRAND_ID = 20;

    public const ARCHIVED_BRAND_ID = 21;

    public const DRAFT_BRAND_ID = 24;

    public static function create(): void
    {
        $activeBrand = CentralBrand::query()->find(self::ACTIVE_BRAND_ID);
        $archivedBrand = CentralBrand::query()->find(self::ARCHIVED_BRAND_ID);
        $draftBrand = CentralBrand::query()->find(self::DRAFT_BRAND_ID);

        if (
            ! $activeBrand instanceof CentralBrand || $activeBrand->slug !== 'samsung'
            || ! $archivedBrand instanceof CentralBrand || $archivedBrand->slug !== 'sony'
            || ! $draftBrand instanceof CentralBrand || $draftBrand->slug !== 'zotac'
        ) {
            throw new RuntimeException('BrandDetailFixture requires the deterministic Samsung, Sony, and Zotac BrandListFixture records.');
        }

        foreach ([
            [1201201, 'Samsung Galaxy S26', 'SM-S942', 'samsung-galaxy-s26'],
            [1201202, 'Samsung Galaxy Tab S12', 'SM-X940', 'samsung-galaxy-tab-s12'],
            [1201203, 'Samsung Odyssey G9', 'LS49CG', 'samsung-odyssey-g9'],
        ] as [$id, $name, $model, $slug]) {
            $product = new CentralProduct;
            $product->forceFill([
                'id' => $id,
                'central_brand_id' => $activeBrand->getKey(),
                'central_category_id' => null,
                'name' => $name,
                'model' => $model,
                'slug' => $slug,
                'status' => CentralProductStatus::Draft,
                'version' => 1,
                'created_at' => CarbonImmutable::parse('2026-08-09T10:00:00Z'),
                'updated_at' => CarbonImmutable::parse('2026-08-09T10:00:00Z'),
            ])->saveOrFail();
        }
    }

    private function __construct() {}
}
