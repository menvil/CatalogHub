<?php

declare(strict_types=1);

namespace Tests\Support;

use Carbon\CarbonImmutable;
use Closure;

final class UiFixture
{
    public const LOCALE = 'en-US';

    public const TIMEZONE = 'UTC';

    public const CLOCK = '2026-08-09T10:00:00+00:00';

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function withFrozenClock(Closure $callback): mixed
    {
        $originalTimezone = date_default_timezone_get();
        $originalNow = CarbonImmutable::getTestNow();

        date_default_timezone_set(self::TIMEZONE);
        CarbonImmutable::setTestNow(CarbonImmutable::parse(self::CLOCK));

        try {
            return $callback();
        } finally {
            CarbonImmutable::setTestNow($originalNow);
            date_default_timezone_set($originalTimezone);
        }
    }
}
