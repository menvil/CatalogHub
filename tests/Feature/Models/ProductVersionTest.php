<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exposes_product_actor_and_json_version_data(): void
    {
        $product = CentralProduct::factory()->create();
        $actor = User::factory()->create();
        $version = ProductVersion::factory()->create([
            'central_product_id' => $product->id,
            'changed_by_user_id' => $actor->id,
            'version' => 3,
            'snapshot_json' => ['name' => 'Monitor'],
            'diff_json' => ['name' => ['old' => 'Old', 'new' => 'Monitor']],
            'metadata_json' => ['source' => 'correction'],
        ]);

        $this->assertTrue($version->centralProduct->is($product));
        $this->assertTrue($version->changedBy->is($actor));
        $this->assertSame(3, $version->version);
        $this->assertSame(['name' => 'Monitor'], $version->snapshot_json);
        $this->assertSame(['name' => ['old' => 'Old', 'new' => 'Monitor']], $version->diff_json);
        $this->assertSame(['source' => 'correction'], $version->metadata_json);
    }

    public function test_changed_by_relation_is_nullable(): void
    {
        $version = ProductVersion::factory()->create(['changed_by_user_id' => null]);

        $this->assertNull($version->changedBy);
    }
}
