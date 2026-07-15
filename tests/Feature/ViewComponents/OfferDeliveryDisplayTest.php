<?php

namespace Tests\Feature\ViewComponents;

use App\Models\MarketOffer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class OfferDeliveryDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_a_formatted_delivery_price_in_offer_cards_and_tables(): void
    {
        $offer = MarketOffer::factory()->create([
            'delivery_price' => '12.50',
            'currency' => 'EUR',
        ]);
        $offer->load('merchant.logoMediaAsset');

        $this->assertStringContainsString('Delivery: €12.50', $this->renderCard($offer));
        $this->assertStringContainsString('€12.50', $this->renderTable($offer));
    }

    public function test_it_shows_free_delivery_when_the_delivery_price_is_zero(): void
    {
        $offer = MarketOffer::factory()->create(['delivery_price' => '0.00']);
        $offer->load('merchant.logoMediaAsset');

        $this->assertStringContainsString('Free delivery', $this->renderCard($offer));
        $this->assertStringContainsString('Free delivery', $this->renderTable($offer));
    }

    public function test_it_shows_an_unknown_state_when_the_delivery_price_is_missing(): void
    {
        $offer = MarketOffer::factory()->create(['delivery_price' => null]);
        $offer->load('merchant.logoMediaAsset');

        $this->assertStringContainsString('Delivery price unknown', $this->renderCard($offer));
        $this->assertStringContainsString('Delivery price unknown', $this->renderTable($offer));
    }

    private function renderCard(MarketOffer $offer): string
    {
        return Blade::render(
            '<x-public.offer-card :offer="$offer" locale="en" />',
            compact('offer'),
        );
    }

    private function renderTable(MarketOffer $offer): string
    {
        return Blade::render(
            '<x-public.offer-table :offers="$offers" locale="en" />',
            ['offers' => new Collection([$offer])],
        );
    }
}
