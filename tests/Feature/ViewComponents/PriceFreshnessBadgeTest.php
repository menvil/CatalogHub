<?php

namespace Tests\Feature\ViewComponents;

use App\Enums\PriceFreshnessStatus;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PriceFreshnessBadgeTest extends TestCase
{
    public function test_it_renders_all_supported_price_freshness_states(): void
    {
        $expectations = [
            PriceFreshnessStatus::Fresh->value => ['Updated recently', 'emerald'],
            PriceFreshnessStatus::Stale->value => ['Price may be outdated', 'amber'],
            PriceFreshnessStatus::Expired->value => ['Outdated price', 'red'],
            PriceFreshnessStatus::Unknown->value => ['Update time unknown', 'slate'],
        ];

        foreach ($expectations as $status => [$label, $color]) {
            $html = Blade::render(
                '<x-public.price-freshness-badge :status="$status" />',
                ['status' => PriceFreshnessStatus::from($status)],
            );

            $this->assertStringContainsString('data-price-freshness="'.$status.'"', $html);
            $this->assertStringContainsString($label, $html);
            $this->assertStringContainsString($color, $html);
        }
    }

    public function test_badge_accepts_a_serialized_status_without_calculating_freshness(): void
    {
        $html = Blade::render('<x-public.price-freshness-badge status="stale" />');

        $this->assertStringContainsString('data-price-freshness="stale"', $html);
        $this->assertStringContainsString('Price may be outdated', $html);
    }
}
