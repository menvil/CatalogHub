<?php

namespace Tests\Feature\Models;

use App\Models\CatalogSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_a_snapshot_with_casts_and_relations(): void
    {
        $snapshot = CatalogSnapshot::factory()->create([
            'status' => 'pending',
            'files_json' => ['products' => ['path' => 'snapshots/test/products.jsonl']],
            'metadata_json' => ['requested_sections' => ['products']],
        ]);

        $this->assertTrue($snapshot->exists);
        $this->assertSame('pending', $snapshot->status);
        $this->assertSame('snapshots/test/products.jsonl', $snapshot->files_json['products']['path']);
        $this->assertSame(['requested_sections' => ['products']], $snapshot->metadata_json);
        $this->assertNotNull($snapshot->createdBy);
    }

    public function test_status_helpers_manage_the_snapshot_lifecycle(): void
    {
        $snapshot = CatalogSnapshot::factory()->create();

        $snapshot->markGenerating();
        $this->assertSame('generating', $snapshot->status);
        $this->assertNotNull($snapshot->started_at);

        $snapshot->markCompleted([
            'products' => ['path' => 'snapshots/test/products.jsonl', 'line_count' => 2],
        ]);
        $this->assertTrue($snapshot->isCompleted());
        $this->assertFalse($snapshot->isFailed());
        $this->assertNotNull($snapshot->completed_at);
        $this->assertSame(2, $snapshot->files_json['products']['line_count']);

        $failed = CatalogSnapshot::factory()->create();
        $failed->markFailed('Disk unavailable.');

        $this->assertTrue($failed->isFailed());
        $this->assertSame('Disk unavailable.', $failed->failure_reason);
        $this->assertNotNull($failed->failed_at);
    }
}
