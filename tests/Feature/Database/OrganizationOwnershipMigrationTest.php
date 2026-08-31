<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralBrandOwnership;
use App\Models\Organization;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class OrganizationOwnershipMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_has_normalized_organization_identity_and_one_owner_per_brand(): void
    {
        self::assertTrue(Schema::hasColumns('organizations', [
            'id', 'name', 'normalized_name', 'created_at', 'updated_at',
        ]));
        self::assertTrue(Schema::hasColumns('central_brand_ownerships', [
            'id', 'central_brand_id', 'organization_id', 'created_at', 'updated_at',
        ]));

        $organizationIndexes = collect(Schema::getIndexes('organizations'));
        self::assertTrue($organizationIndexes->contains(
            static fn (array $index): bool => $index['columns'] === ['normalized_name'],
        ));

        $ownershipIndexes = collect(Schema::getIndexes('central_brand_ownerships'));
        self::assertTrue($ownershipIndexes->contains(
            static fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['central_brand_id'],
        ));
        self::assertTrue($ownershipIndexes->contains(
            static fn (array $index): bool => $index['columns'] === ['organization_id'],
        ));

        foreach (['parent_company', 'parent_company_name', 'parent_brand_id', 'owner_name', 'organization_id'] as $column) {
            self::assertFalse(Schema::hasColumn('central_brands', $column));
        }
    }

    public function test_database_allows_many_brands_per_organization_but_rejects_two_owners_for_one_brand(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $brandA = CentralBrand::factory()->create();
        $brandB = CentralBrand::factory()->create();
        CentralBrandOwnership::factory()->create([
            'central_brand_id' => $brandA->id,
            'organization_id' => $organization->id,
        ]);
        CentralBrandOwnership::factory()->create([
            'central_brand_id' => $brandB->id,
            'organization_id' => $organization->id,
        ]);

        self::assertCount(2, $organization->ownedBrands);

        try {
            DB::transaction(static function () use ($brandA, $otherOrganization): void {
                CentralBrandOwnership::factory()->create([
                    'central_brand_id' => $brandA->id,
                    'organization_id' => $otherOrganization->id,
                ]);
            });
            self::fail('Expected the one-current-owner database invariant to reject a second row.');
        } catch (QueryException) {
            self::assertSame(1, CentralBrandOwnership::query()->where('central_brand_id', $brandA->id)->count());
        }
    }

    public function test_foreign_keys_restrict_brand_and_organization_deletion_until_ownership_is_cleared(): void
    {
        $organization = Organization::factory()->create();
        $brand = CentralBrand::factory()->create();
        $ownership = CentralBrandOwnership::factory()->create([
            'central_brand_id' => $brand->id,
            'organization_id' => $organization->id,
        ]);

        foreach ([
            static fn (): int => DB::table('central_brands')->where('id', $brand->id)->delete(),
            static fn (): int => DB::table('organizations')->where('id', $organization->id)->delete(),
        ] as $delete) {
            try {
                DB::transaction($delete, attempts: 1);
                self::fail('Expected ownership foreign keys to restrict deletion.');
            } catch (QueryException) {
                self::assertDatabaseHas('central_brand_ownerships', ['id' => $ownership->id]);
            }
        }

        $ownership->deleteOrFail();
        $brand->deleteOrFail();
        $organization->deleteOrFail();
        self::assertDatabaseCount('central_brand_ownerships', 0);
    }
}
