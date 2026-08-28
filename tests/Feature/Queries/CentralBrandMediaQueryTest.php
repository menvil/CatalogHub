<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\MediaVariant;
use App\Queries\CentralCatalog\CentralBrandMediaQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\DatabaseQueryCounter;
use Tests\TestCase;

final class CentralBrandMediaQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_logo_selector_requires_an_exact_global_primary_assignment(): void
    {
        $privateBrand = CentralBrand::factory()->create();
        MediaAssignment::factory()->for(MediaAsset::factory(), 'asset')->create([
            'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            'entity_id' => $privateBrand->id,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
            'locale' => null,
            'site_id' => null,
            'market_id' => null,
            'is_primary' => true,
            'visibility' => 'private',
        ]);
        $secondaryBrand = CentralBrand::factory()->create();
        MediaAssignment::factory()->for(MediaAsset::factory(), 'asset')->create([
            'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            'entity_id' => $secondaryBrand->id,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
            'locale' => null,
            'site_id' => null,
            'market_id' => null,
            'is_primary' => false,
            'visibility' => 'global',
        ]);
        $globalBrand = CentralBrand::factory()->create();
        $globalAsset = MediaAsset::factory()->create();
        $globalAssignment = MediaAssignment::factory()->for($globalAsset, 'asset')->create([
            'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            'entity_id' => $globalBrand->id,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
            'locale' => null,
            'site_id' => null,
            'market_id' => null,
            'is_primary' => true,
            'visibility' => 'global',
        ]);
        $query = app(CentralBrandMediaQuery::class);

        self::assertNull($query->primaryLogoAssignmentFor($privateBrand));
        self::assertNull($query->primaryLogoAssignmentFor($secondaryBrand));
        $selectedAssignment = $query->primaryLogoAssignmentFor($globalBrand);
        $selectedAsset = $query->logoFor($globalBrand);
        self::assertNotNull($selectedAssignment);
        self::assertNotNull($selectedAsset);
        self::assertSame($globalAssignment->id, $selectedAssignment->id);
        self::assertSame($globalAsset->id, $selectedAsset->id);
    }

    public function test_selector_ignores_locale_site_and_market_scopes(): void
    {
        $brand = CentralBrand::factory()->create();

        foreach ([
            ['locale' => 'de-DE', 'site_id' => null, 'market_id' => null],
            ['locale' => null, 'site_id' => 10, 'market_id' => null],
            ['locale' => null, 'site_id' => null, 'market_id' => 20],
        ] as $context) {
            MediaAssignment::factory()->create([
                'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
                'entity_id' => $brand->id,
                'role' => MediaAssignment::ROLE_BRAND_LOGO,
                ...$context,
                'is_primary' => true,
                'visibility' => 'global',
            ]);
        }

        self::assertNull(app(CentralBrandMediaQuery::class)->primaryLogoAssignmentFor($brand));
    }

    public function test_selector_eager_loads_only_global_brand_logo_variants_with_bounded_queries(): void
    {
        $brand = CentralBrand::factory()->create();
        $asset = MediaAsset::factory()->create();
        MediaAssignment::factory()->for($asset, 'asset')->create([
            'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            'entity_id' => $brand->id,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
            'locale' => null,
            'site_id' => null,
            'market_id' => null,
            'is_primary' => true,
            'visibility' => 'global',
        ]);
        MediaVariant::factory()->for($asset, 'asset')->create(['variant_type' => 'brand_logo_128']);
        MediaVariant::factory()->for($asset, 'asset')->create(['variant_type' => 'thumbnail']);
        MediaVariant::factory()->for($asset, 'asset')->create([
            'variant_type' => 'brand_logo_256',
            'locale' => 'de-DE',
        ]);

        $measured = DatabaseQueryCounter::measure(
            fn () => app(CentralBrandMediaQuery::class)->primaryLogoAssignmentFor($brand),
        );
        $assignment = $measured['result'];

        self::assertInstanceOf(MediaAssignment::class, $assignment);
        self::assertTrue($assignment->relationLoaded('asset'));
        self::assertTrue($assignment->asset->relationLoaded('variants'));
        self::assertSame(['brand_logo_128'], $assignment->asset->variants->pluck('variant_type')->all());
        self::assertLessThanOrEqual(3, $measured['count']);
    }
}
