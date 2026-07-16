<?php

namespace Tests\Feature\Corrections;

use App\Actions\Corrections\ApplyCorrectionToCentralAction;
use App\Jobs\Projections\RebuildProductProjectionJob;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ChangeRequest;
use App\Models\SiteProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CorrectionProjectionRebuildTest extends TestCase
{
    use RefreshDatabase;

    public function test_applying_a_correction_queues_every_affected_site_product_for_rebuild(): void
    {
        Queue::fake();

        $admin = User::factory()->centralAdmin()->create();
        $product = CentralProduct::factory()->create(['name' => 'Old title', 'version' => 1]);
        $siteProducts = SiteProduct::factory()->count(2)->for($product, 'centralProduct')->create([
            'published_version' => 1,
            'sync_status' => 'completed',
        ]);
        $request = ChangeRequest::factory()->approved()->create([
            'central_product_id' => $product->id,
            'entity_id' => $product->id,
            'field_path' => 'name',
            'proposed_value_json' => ['value' => 'New title'],
        ]);

        app(ApplyCorrectionToCentralAction::class)->handle($admin, $request);

        foreach ($siteProducts as $siteProduct) {
            Queue::assertPushed(
                RebuildProductProjectionJob::class,
                fn (RebuildProductProjectionJob $job): bool => $job->siteProductId === $siteProduct->id
                    && $job->triggeredByUserId === $admin->id,
            );
            $this->assertDatabaseHas('site_products', [
                'id' => $siteProduct->id,
                'published_version' => 1,
                'sync_status' => 'queued',
            ]);
        }

        Queue::assertPushed(RebuildProductProjectionJob::class, 2);
    }

    public function test_the_rebuild_job_publishes_the_new_central_version_and_logs_the_correction_trigger(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $product = CentralProduct::factory()->create(['version' => 3]);
        $siteProduct = SiteProduct::factory()->for($product, 'centralProduct')->create([
            'published_version' => 2,
            'sync_status' => 'queued',
        ]);

        (new RebuildProductProjectionJob($siteProduct->id, $admin->id))->handle();

        $this->assertDatabaseHas('site_products', [
            'id' => $siteProduct->id,
            'published_version' => 3,
            'sync_status' => 'completed',
        ]);
        $this->assertDatabaseHas('sync_logs', [
            'operation' => 'rebuild_product_projection',
            'triggered_by' => 'correction',
            'site_id' => $siteProduct->site_id,
            'central_product_id' => $product->id,
            'status' => 'completed',
        ]);
    }
}
