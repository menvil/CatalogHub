<?php

namespace Tests\Feature\Models;

use App\Enums\MarketStatus;
use App\Models\Market;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_a_market_with_casts(): void
    {
        $market = Market::factory()->create([
            'config_json' => ['price_sources_enabled' => true],
            'status' => MarketStatus::Active,
        ]);

        $this->assertTrue($market->exists);
        $this->assertNotSame('', trim($market->code));
        $this->assertSame(['price_sources_enabled' => true], $market->config_json);
        $this->assertSame(MarketStatus::Active, $market->status);
    }

    public function test_status_helpers_describe_market_state(): void
    {
        $active = Market::factory()->create(['status' => MarketStatus::Active]);
        $archived = Market::factory()->create(['status' => MarketStatus::Archived]);

        $this->assertTrue($active->isActive());
        $this->assertFalse($active->isArchived());
        $this->assertTrue($archived->isArchived());
        $this->assertFalse($archived->isActive());
    }

    public function test_factory_can_create_more_than_two_letter_code_space(): void
    {
        $markets = Market::factory()->count(677)->create();

        $this->assertCount(677, $markets->pluck('code')->unique());
    }
}
