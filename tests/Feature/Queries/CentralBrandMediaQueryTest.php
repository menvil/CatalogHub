<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Queries\CentralCatalog\CentralBrandMediaQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
