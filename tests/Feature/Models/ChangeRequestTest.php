<?php

namespace Tests\Feature\Models;

use App\Enums\ChangeRequestStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ChangeRequest;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exposes_workflow_relations_and_casts(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $creator = User::factory()->create();
        $reviewer = User::factory()->centralAdmin()->create();
        $applier = User::factory()->centralAdmin()->create();

        $request = ChangeRequest::factory()->create([
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'created_by_user_id' => $creator->id,
            'reviewed_by_user_id' => $reviewer->id,
            'applied_by_user_id' => $applier->id,
            'status' => ChangeRequestStatus::Applied,
            'old_value_json' => ['value' => 144],
            'proposed_value_json' => ['value' => 165],
            'metadata_json' => ['source' => 'manufacturer'],
            'reviewed_at' => now()->subMinute(),
            'applied_at' => now(),
        ]);

        $this->assertTrue($request->site->is($site));
        $this->assertTrue($request->centralProduct->is($product));
        $this->assertTrue($request->createdBy->is($creator));
        $this->assertTrue($request->reviewedBy->is($reviewer));
        $this->assertTrue($request->appliedBy->is($applier));
        $this->assertSame(ChangeRequestStatus::Applied, $request->status);
        $this->assertSame(['value' => 144], $request->old_value_json);
        $this->assertSame(['value' => 165], $request->proposed_value_json);
        $this->assertSame(['source' => 'manufacturer'], $request->metadata_json);
        $this->assertNotNull($request->reviewed_at);
        $this->assertNotNull($request->applied_at);
    }

    public function test_factory_exposes_all_workflow_states(): void
    {
        $this->assertSame(ChangeRequestStatus::Pending, ChangeRequest::factory()->pending()->create()->status);
        $this->assertSame(ChangeRequestStatus::Approved, ChangeRequest::factory()->approved()->create()->status);
        $this->assertSame(ChangeRequestStatus::Rejected, ChangeRequest::factory()->rejected()->create()->status);
        $this->assertSame(ChangeRequestStatus::Applied, ChangeRequest::factory()->applied()->create()->status);
    }
}
