<?php

declare(strict_types=1);

namespace Tests\Support;

use Closure;

trait ClockFixtures
{
    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    protected function withFoundationClock(Closure $callback): mixed
    {
        return UiFixture::withFrozenClock($callback);
    }
}
