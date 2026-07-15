<?php

namespace Tests\Feature\Models;

use App\Enums\PriceSourceStatus;
use App\Enums\PriceSourceType;
use App\Models\Market;
use App\Models\PriceSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_price_source_with_casts_and_market(): void
    {
        $source = PriceSource::factory()->create([
            'type' => PriceSourceType::Api,
            'status' => PriceSourceStatus::Active,
            'config_json' => ['trusted' => true],
            'last_sync_at' => now(),
        ]);

        $this->assertTrue($source->exists);
        $this->assertSame(PriceSourceType::Api, $source->type);
        $this->assertSame(PriceSourceStatus::Active, $source->status);
        $this->assertSame(['trusted' => true], $source->config_json);
        $this->assertNotNull($source->last_sync_at);
        $this->assertInstanceOf(Market::class, $source->market);
    }

    public function test_defines_price_pipeline_relationships(): void
    {
        foreach (['credentials', 'syncLogs', 'rawOffers', 'mappings', 'offers'] as $relationship) {
            $this->assertTrue(method_exists(PriceSource::class, $relationship));
        }
    }
}
