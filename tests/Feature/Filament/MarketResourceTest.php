<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\MarketResource;
use App\Filament\Resources\MarketResource\Pages\CreateMarket;
use App\Filament\Resources\MarketResource\Pages\EditMarket;
use App\Filament\Resources\MarketResource\Pages\ListMarkets;
use App\Models\Market;
use Tests\TestCase;

class MarketResourceTest extends TestCase
{
    public function test_has_market_resource_and_crud_pages(): void
    {
        $this->assertTrue(class_exists(MarketResource::class));
        $this->assertSame(Market::class, MarketResource::getModel());
        $this->assertArrayHasKey('index', MarketResource::getPages());
        $this->assertArrayHasKey('create', MarketResource::getPages());
        $this->assertArrayHasKey('edit', MarketResource::getPages());
        $this->assertTrue(class_exists(ListMarkets::class));
        $this->assertTrue(class_exists(CreateMarket::class));
        $this->assertTrue(class_exists(EditMarket::class));
    }
}
