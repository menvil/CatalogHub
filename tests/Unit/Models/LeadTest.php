<?php

namespace Tests\Unit\Models;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Lead;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_relationships_and_enum_casts_work(): void
    {
        $lead = Lead::factory()->create([
            'type' => LeadType::BuyingAdvice,
            'status' => LeadStatus::New,
            'metadata' => ['campaign' => 'summer'],
        ]);

        $this->assertInstanceOf(Site::class, $lead->site);
        $this->assertInstanceOf(CentralProduct::class, $lead->centralProduct);
        $this->assertInstanceOf(CentralCategory::class, $lead->centralCategory);
        $this->assertSame(LeadType::BuyingAdvice, $lead->type);
        $this->assertSame(LeadStatus::New, $lead->status);
        $this->assertSame(['campaign' => 'summer'], $lead->metadata);
    }

    public function test_lead_scopes_filter_by_site_new_status_and_spam(): void
    {
        $site = Site::factory()->create();
        $newLead = Lead::factory()->create(['site_id' => $site->id, 'status' => LeadStatus::New]);
        Lead::factory()->create(['site_id' => $site->id, 'status' => LeadStatus::Spam]);
        Lead::factory()->create();

        $leads = Lead::query()->forSite($site)->new()->notSpam()->get();

        $this->assertCount(1, $leads);
        $this->assertTrue($leads->first()->is($newLead));
    }
}
