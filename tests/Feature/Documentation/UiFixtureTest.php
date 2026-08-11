<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use Carbon\CarbonImmutable;
use Tests\Support\UiFixture;
use Tests\TestCase;

final class UiFixtureTest extends TestCase
{
    public function test_fixture_clock_and_timezone_are_fixed_only_inside_the_callback(): void
    {
        $originalTimezone = date_default_timezone_get();
        $originalNow = CarbonImmutable::getTestNow();

        $result = UiFixture::withFrozenClock(fn (): array => [
            'now' => CarbonImmutable::now()->toIso8601String(),
            'timezone' => date_default_timezone_get(),
        ]);

        $this->assertSame(UiFixture::CLOCK, $result['now']);
        $this->assertSame(UiFixture::TIMEZONE, $result['timezone']);
        $this->assertSame($originalTimezone, date_default_timezone_get());
        $this->assertSame($originalNow, CarbonImmutable::getTestNow());
    }

    public function test_fixture_clock_and_timezone_are_restored_when_the_callback_throws(): void
    {
        $originalTimezone = date_default_timezone_get();
        $originalNow = CarbonImmutable::getTestNow();
        $originalFailure = getenv('CATALOGHUB_FIXTURE_FAILURE');

        try {
            putenv('CATALOGHUB_FIXTURE_FAILURE=1');
            UiFixture::withFrozenClock(static function (): string {
                if (getenv('CATALOGHUB_FIXTURE_FAILURE') === '1') {
                    throw new \RuntimeException('fixture failure');
                }

                return 'unexpected success';
            });
            $this->fail('Expected fixture exception to propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('fixture failure', $exception->getMessage());
        } finally {
            putenv($originalFailure === false ? 'CATALOGHUB_FIXTURE_FAILURE' : 'CATALOGHUB_FIXTURE_FAILURE='.$originalFailure);
        }

        $this->assertSame($originalTimezone, date_default_timezone_get());
        $this->assertSame($originalNow, CarbonImmutable::getTestNow());
    }
}
