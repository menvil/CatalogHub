<?php

namespace Tests\Feature\Health;

use App\Services\Health\QueueHealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class QueueHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_healthy_queue_configuration(): void
    {
        config()->set('queue.default', 'database');
        config()->set('queue.failed.database', config('database.default'));

        $result = app(QueueHealthCheck::class)->run();

        $this->assertSame('ok', $result->status);
        $this->assertSame('database', $result->details['connection']);
        $this->assertSame(0, $result->details['recent_failed_jobs']);
    }

    public function test_it_warns_when_production_queue_runs_synchronously(): void
    {
        config()->set('app.env', 'production');
        config()->set('queue.default', 'sync');
        config()->set('queue.failed.database', config('database.default'));

        $result = app(QueueHealthCheck::class)->run();

        $this->assertSame('warning', $result->status);
        $this->assertFalse($result->details['asynchronous']);
    }

    public function test_it_reports_recent_failed_jobs(): void
    {
        config()->set('queue.default', 'database');
        config()->set('queue.failed.database', config('database.default'));

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Test failure',
            'failed_at' => now(),
        ]);

        $result = app(QueueHealthCheck::class)->run();

        $this->assertSame('warning', $result->status);
        $this->assertSame(1, $result->details['recent_failed_jobs']);
    }
}
