<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

final class JobPolicyTest extends TestCase
{
    public function test_standard_job_policies_have_finite_positive_attempt_and_timeout_values(): void
    {
        foreach (['fast', 'external', 'batch'] as $policy) {
            $definition = config("jobs.{$policy}");

            $this->assertIsArray($definition);
            $this->assertGreaterThan(0, $definition['tries']);
            $this->assertGreaterThan(0, $definition['timeout']);
            $this->assertIsArray($definition['backoff']);
        }
    }

    public function test_external_jobs_back_off_and_finish_before_the_redis_retry_window(): void
    {
        $external = config('jobs.external');

        $this->assertSame([10, 60], $external['backoff']);
        $this->assertLessThan(config('queue.connections.redis.retry_after'), $external['timeout']);
        $this->assertSame(3, $external['tries']);
    }

    public function test_non_retryable_exceptions_and_uniqueness_window_are_explicit(): void
    {
        $this->assertSame([
            InvalidArgumentException::class,
            LogicException::class,
        ], config('jobs.non_retryable_exceptions'));
        $this->assertSame(300, config('jobs.unique_for'));
    }
}
