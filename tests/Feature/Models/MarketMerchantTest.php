<?php

namespace Tests\Feature\Models;

use App\Enums\MarketMerchantStatus;
use App\Models\Market;
use App\Models\MarketMerchant;
use App\Models\MediaAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketMerchantTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_market_merchant_with_casts_and_relationships(): void
    {
        $merchant = MarketMerchant::factory()->create([
            'status' => MarketMerchantStatus::Active,
            'metadata' => ['verified' => true],
            'logo_media_asset_id' => MediaAsset::factory(),
        ]);

        $this->assertInstanceOf(Market::class, $merchant->market);
        $this->assertInstanceOf(MediaAsset::class, $merchant->logoMediaAsset);
        $this->assertSame(MarketMerchantStatus::Active, $merchant->status);
        $this->assertSame(['verified' => true], $merchant->metadata);
        $this->assertTrue(method_exists($merchant, 'offers'));
    }
}
