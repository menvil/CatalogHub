<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\Middleware\ApplyJobContext;
use App\Jobs\RecordFoundationHeartbeatJob;
use App\Models\JobIdempotencyRecord;
use App\Services\Operations\FoundationHeartbeatRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

final class IdempotentJobTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('invalidIdempotencyKeys')]
    public function test_job_rejects_invalid_idempotency_keys(string $idempotencyKey): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RecordFoundationHeartbeatJob($idempotencyKey);
    }

    #[DataProvider('invalidIdempotencyKeys')]
    public function test_recorder_rejects_invalid_idempotency_keys(string $idempotencyKey): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(FoundationHeartbeatRecorder::class)->record($idempotencyKey);
    }

    public function test_repeated_execution_records_one_logical_foundation_effect(): void
    {
        $job = new RecordFoundationHeartbeatJob('foundation-heartbeat:2026-08-11T12:00:00Z');
        $recorder = app(FoundationHeartbeatRecorder::class);

        $job->handle($recorder);
        $job->handle($recorder);

        $this->assertSame(1, JobIdempotencyRecord::query()->count());
        $this->assertDatabaseHas('job_idempotency_records', [
            'idempotency_key' => 'foundation-heartbeat:2026-08-11T12:00:00Z',
        ]);
    }

    public function test_failure_before_recording_can_retry_safely(): void
    {
        $job = new RecordFoundationHeartbeatJob('foundation-heartbeat:retry-safe');
        $failingRecorder = $this->createMock(FoundationHeartbeatRecorder::class);
        $failingRecorder->method('record')->willThrowException(new RuntimeException('temporary failure'));

        try {
            $job->handle($failingRecorder);
            self::fail('The simulated pre-completion failure must escape the job.');
        } catch (RuntimeException $exception) {
            self::assertSame('temporary failure', $exception->getMessage());
        }

        $this->assertDatabaseCount('job_idempotency_records', 0);

        $job->handle(app(FoundationHeartbeatRecorder::class));

        $this->assertDatabaseCount('job_idempotency_records', 1);
    }

    public function test_job_uses_the_standard_context_and_fast_job_policy(): void
    {
        $job = new RecordFoundationHeartbeatJob('foundation-heartbeat:context', 42);

        $this->assertSame('foundation-heartbeat:context', $job->uniqueId());
        $this->assertSame(config('jobs.fast.tries'), $job->tries);
        $this->assertSame(config('jobs.fast.timeout'), $job->timeout);
        $this->assertSame(config('jobs.unique_for'), $job->uniqueFor);
        $this->assertInstanceOf(ApplyJobContext::class, $job->middleware()[0]);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidIdempotencyKeys(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace only' => ['   '];
        yield 'longer than the database column' => [str_repeat('a', 256)];
    }
}
