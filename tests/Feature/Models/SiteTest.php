<?php

namespace Tests\Feature\Models;

use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Models\Market;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_site_for_market_with_typed_state(): void
    {
        $market = Market::factory()->create();
        $site = Site::factory()->for($market)->create(['mode' => SiteMode::SingleCategory, 'status' => SiteStatus::Active]);

        $this->assertTrue($site->market->is($market));
        $this->assertTrue($site->isSingleCategory());
        $this->assertTrue($site->isActive());
    }

    public function test_site_mode_helpers_are_mutually_exclusive(): void
    {
        $site = Site::factory()->create(['mode' => SiteMode::MultiCategory, 'status' => SiteStatus::Archived]);

        $this->assertTrue($site->isMultiCategory());
        $this->assertFalse($site->isSingleCategory());
        $this->assertFalse($site->isActive());
    }
}
