<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use Carbon\CarbonImmutable;
use RuntimeException;

final class BrandDetailFixture
{
    public const VERSION = 'brand-detail-v3';

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

        $activeBrand->forceFill([
            'website_url' => 'https://www.samsung.com/',
            'country_id' => CountryReference::id('KR'),
            'founded_year' => 1938,
            'support_url' => 'https://www.samsung.com/support/',
            'contact_email' => 'support@example.com',
            'primary_color' => '#1428A0',
        ])->saveOrFail();

        $categories = collect([
            [120121, 'Smartphones', 'smartphones'],
            [120122, 'Televisions', 'televisions'],
            [120123, 'Tablets', 'tablets'],
            [120124, 'Laptops', 'laptops'],
        ])->mapWithKeys(function (array $record): array {
            [$id, $name, $slug] = $record;
            $category = CentralCategory::factory()->create(['id' => $id, 'name' => $name, 'slug' => $slug]);

            return [$slug => $category];
        });

        $products = [
            ['Samsung Galaxy S26', 'SM-S942', 'samsung-galaxy-s26', 'smartphones', CentralProductStatus::Active],
            ['Samsung Galaxy Tab S12', 'SM-X940', 'samsung-galaxy-tab-s12', 'tablets', CentralProductStatus::Active],
            ['Samsung Neo QLED TV', 'QN90F', 'samsung-neo-qled-tv', 'televisions', CentralProductStatus::Draft],
            ['Samsung Legacy Laptop', 'NP-OLD', 'samsung-legacy-laptop', 'laptops', CentralProductStatus::Archived],
        ];

        foreach ([['smartphones', 23], ['televisions', 11], ['tablets', 5]] as [$slug, $additional]) {
            for ($index = 1; $index <= $additional; $index++) {
                $products[] = [
                    "Samsung {$slug} fixture {$index}",
                    strtoupper(substr($slug, 0, 3)).'-'.$index,
                    "samsung-{$slug}-fixture-{$index}",
                    $slug,
                    CentralProductStatus::Active,
                ];
            }
        }

        foreach ($products as $offset => [$name, $model, $slug, $categorySlug, $status]) {
            $product = new CentralProduct;
            $product->forceFill([
                'id' => 1201201 + $offset,
                'central_brand_id' => $activeBrand->getKey(),
                'central_category_id' => $categories->get($categorySlug)?->getKey(),
                'name' => $name,
                'model' => $model,
                'slug' => $slug,
                'status' => $status,
                'version' => 1,
                'created_at' => CarbonImmutable::parse('2026-08-09T10:00:00Z'),
                'updated_at' => CarbonImmutable::parse('2026-08-09T10:00:00Z'),
            ])->saveOrFail();
        }
    }

    private function __construct() {}
}
