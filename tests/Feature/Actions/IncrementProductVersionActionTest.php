<?php

namespace Tests\Feature\Actions;

use App\Actions\Versions\IncrementProductVersionAction;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ProductVersion;
use App\Models\Site;
use App\Models\SiteOverride;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IncrementProductVersionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_increments_product_version_and_writes_history_atomically(): void
    {
        $product = CentralProduct::factory()->create(['version' => 1]);
        $actor = User::factory()->centralAdmin()->create();

        $version = app(IncrementProductVersionAction::class)->handle(
            product: $product,
            changedBy: $actor,
            changeType: 'manual_update',
            reason: 'Corrected specs.',
            diff: ['name' => ['old' => 'A', 'new' => 'B']],
            snapshot: ['name' => 'B'],
            metadata: ['source' => 'admin'],
        );

        $this->assertTrue(Schema::hasColumn('central_products', 'version'));
        $this->assertSame(2, $product->fresh()->version);
        $this->assertSame(2, $version->version);
        $this->assertTrue($version->changedBy->is($actor));
        $this->assertSame('Corrected specs.', $version->reason);
        $this->assertSame(['name' => ['old' => 'A', 'new' => 'B']], $version->diff_json);
        $this->assertSame(['source' => 'admin'], $version->metadata_json);
        $this->assertSame(['name' => 'B'], $version->snapshot_json);
        $this->assertDatabaseCount('central_product_versions', 1);
    }

    public function test_history_failure_rolls_back_product_increment(): void
    {
        $product = CentralProduct::factory()->create(['version' => 1]);
        ProductVersion::factory()->create([
            'central_product_id' => $product->id,
            'version' => 2,
        ]);

        try {
            app(IncrementProductVersionAction::class)->handle(
                $product,
                null,
                'manual_update',
            );

            $this->fail('A duplicate version history row was accepted.');
        } catch (QueryException) {
            $this->assertSame(1, $product->fresh()->version);
        }
    }

    public function test_local_override_does_not_increment_canonical_version(): void
    {
        $product = CentralProduct::factory()->create(['version' => 4]);

        SiteOverride::query()->create([
            'site_id' => Site::factory()->create()->id,
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'field' => 'local_title',
            'locale_code' => '',
            'value_json' => ['value' => 'Local title'],
            'status' => 'active',
        ]);

        $this->assertSame(4, $product->fresh()->version);
        $this->assertDatabaseCount('central_product_versions', 0);
    }
}
