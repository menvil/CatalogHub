<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\Middleware\ApplyJobContext;
use App\Support\Jobs\JobContext;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

final class JobContextMiddlewareTest extends TestCase
{
    public function test_job_context_is_applied_to_logs_and_cleared_afterward(): void
    {
        $contexts = [];
        $this->captureLogContexts($contexts);
        $context = new JobContext('job-correlation-123', 42);

        (new ApplyJobContext($context))->handle($this->job('queue-job-123'), static function (): void {
            Log::info('Job context fixture reached.');
        });
        Log::info('Outside job context.');

        $this->assertSame([
            'job_id' => 'queue-job-123',
            'correlation_id' => 'job-correlation-123',
            'site_id' => 42,
        ], $contexts['Job context fixture reached.']);
        $this->assertArrayNotHasKey('correlation_id', $contexts['Outside job context.']);
        $this->assertArrayNotHasKey('site_id', $contexts['Outside job context.']);
    }

    public function test_failed_job_log_keeps_its_explicit_correlation_context(): void
    {
        $contexts = [];
        $this->captureLogContexts($contexts);
        $context = new JobContext('job-failure-123');

        try {
            (new ApplyJobContext($context))->handle($this->job('queue-job-failure'), static function (): never {
                Log::error('Job context failure fixture reached.');

                throw new RuntimeException('Job context fixture failed.');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Job context fixture failed.', $exception->getMessage());
        }

        $this->assertSame([
            'job_id' => 'queue-job-failure',
            'correlation_id' => 'job-failure-123',
        ], $contexts['Job context failure fixture reached.']);
    }

    /** @param array<string, array<string, mixed>> $contexts */
    private function captureLogContexts(array &$contexts): void
    {
        Event::listen(MessageLogged::class, static function (MessageLogged $event) use (&$contexts): void {
            $contexts[$event->message] = $event->context;
        });
    }

    private function job(string $id): Job
    {
        $job = $this->createMock(Job::class);
        $job->method('getJobId')->willReturn($id);

        return $job;
    }
}
