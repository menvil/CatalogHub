<?php

namespace Tests\Unit\Pricing;

use App\Enums\PriceFreshnessStatus;
use App\Services\Pricing\PriceFreshnessCalculator;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class PriceFreshnessCalculatorTest extends TestCase
{
    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('pricing.freshness', [
            'fresh_hours' => 6,
            'stale_hours' => 24,
            'expired_hours' => 48,
        ]);
        $this->now = CarbonImmutable::parse('2026-07-15 12:00:00 UTC');
    }

    public function test_it_marks_a_recently_checked_price_as_fresh(): void
    {
        $status = app(PriceFreshnessCalculator::class)->calculate(
            $this->now->subHours(6),
            $this->now,
        );

        $this->assertSame(PriceFreshnessStatus::Fresh, $status);
    }

    public function test_it_marks_an_old_but_usable_price_as_stale(): void
    {
        $status = app(PriceFreshnessCalculator::class)->calculate(
            $this->now->subHours(25),
            $this->now,
        );

        $this->assertSame(PriceFreshnessStatus::Stale, $status);
    }

    public function test_it_marks_a_price_at_the_expiry_boundary_as_expired(): void
    {
        $status = app(PriceFreshnessCalculator::class)->calculate(
            $this->now->subHours(48),
            $this->now,
        );

        $this->assertSame(PriceFreshnessStatus::Expired, $status);
    }

    public function test_it_marks_a_missing_timestamp_as_unknown(): void
    {
        $status = app(PriceFreshnessCalculator::class)->calculate(null, $this->now);

        $this->assertSame(PriceFreshnessStatus::Unknown, $status);
    }

    public function test_it_honours_configured_boundary_values(): void
    {
        config()->set('pricing.freshness.fresh_hours', 2);
        config()->set('pricing.freshness.stale_hours', 3);
        config()->set('pricing.freshness.expired_hours', 4);
        $calculator = app(PriceFreshnessCalculator::class);

        $this->assertSame(
            PriceFreshnessStatus::Fresh,
            $calculator->calculate($this->now->subHours(2), $this->now),
        );
        $this->assertSame(
            PriceFreshnessStatus::Stale,
            $calculator->calculate($this->now->subMinutes(150), $this->now),
        );
        $this->assertSame(
            PriceFreshnessStatus::Expired,
            $calculator->calculate($this->now->subHours(4), $this->now),
        );
    }
}
