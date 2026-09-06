<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CatalogTag;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralBrandOwnership;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Organization;
use App\Queries\CentralCatalog\CentralBrandDetailQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CountryReference;
use Tests\Support\DatabaseQueryCounter;
use Tests\TestCase;

final class CentralBrandDetailQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_usage_tags_and_ownership_load_in_a_bounded_number_of_queries(): void
    {
        $brand = CentralBrand::factory()->create(['country_id' => CountryReference::id('KR')]);
        $organization = Organization::factory()->create();
        CentralBrandOwnership::factory()->create([
            'central_brand_id' => $brand->id,
            'organization_id' => $organization->id,
        ]);
        CentralProduct::factory()->count(2)->for($brand, 'brand')->create();
        CentralProduct::factory()->for($brand, 'brand')->create([
            'status' => CentralProductStatus::Archived,
        ]);
        $tag = CatalogTag::factory()->create();
        $brand->tags()->attach($tag);

        $measured = DatabaseQueryCounter::measure(
            fn () => app(CentralBrandDetailQuery::class)->loadUsage($brand),
        );
        $loaded = $measured['result'];

        self::assertLessThanOrEqual(6, $measured['count']);
        self::assertSame(2, $loaded->products_count);
        self::assertTrue($loaded->relationLoaded('country'));
        self::assertTrue($loaded->country->relationLoaded('translations'));
        self::assertTrue($loaded->relationLoaded('tags'));
        self::assertTrue($loaded->relationLoaded('ownership'));
        self::assertTrue($loaded->ownership->relationLoaded('organization'));
        self::assertSame($organization->id, $loaded->ownership->organization->id);
        self::assertFalse($loaded->relationLoaded('products'));

        CentralProduct::factory()->count(20)->for($brand, 'brand')->create();
        $brand->unsetRelations();
        $expanded = DatabaseQueryCounter::measure(
            fn () => app(CentralBrandDetailQuery::class)->loadUsage($brand),
        );

        self::assertSame($measured['count'], $expanded['count']);
        self::assertSame(22, $expanded['result']->products_count);
        self::assertFalse($expanded['result']->relationLoaded('products'));
    }

    public function test_absent_parent_company_is_loaded_without_lazy_queries(): void
    {
        $brand = CentralBrand::factory()->create();
        $loaded = app(CentralBrandDetailQuery::class)->loadUsage($brand);

        self::assertTrue($loaded->relationLoaded('ownership'));
        self::assertNull($loaded->ownership);
    }

    public function test_recent_products_are_bounded_current_eager_loaded_and_deterministic(): void
    {
        $brand = CentralBrand::factory()->create();
        $other = CentralBrand::factory()->create();

        foreach (range(1, 7) as $index) {
            CentralProduct::factory()->for($brand, 'brand')->create([
                'name' => 'Current product '.$index,
                'updated_at' => now()->subDays($index),
            ]);
        }
        CentralProduct::factory()->for($brand, 'brand')->create([
            'name' => 'Archived newest product',
            'status' => CentralProductStatus::Archived,
            'updated_at' => now()->addDay(),
        ]);
        CentralProduct::factory()->for($other, 'brand')->create([
            'name' => 'Other brand product',
            'updated_at' => now()->addDays(2),
        ]);

        $measured = DatabaseQueryCounter::measure(
            fn () => app(CentralBrandDetailQuery::class)->recentProducts($brand),
        );

        self::assertLessThanOrEqual(2, $measured['count']);
        self::assertSame(5, $measured['result']->count());
        self::assertSame('Current product 1', $measured['result']->first()->name);
        self::assertSame('Current product 5', $measured['result']->last()->name);
        self::assertTrue($measured['result']->every(
            static fn (CentralProduct $product): bool => $product->relationLoaded('category'),
        ));
    }
}
