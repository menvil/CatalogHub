<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class QueueSchedulerRunbookTest extends TestCase
{
    public function test_runbook_references_existing_operational_commands_and_configuration(): void
    {
        $runbook = file_get_contents(base_path('docs/operations/queue-scheduler-runbook.md'));

        self::assertIsString($runbook);
        self::assertArrayHasKey('queue:work', Artisan::all());
        self::assertArrayHasKey('queue:restart', Artisan::all());
        self::assertArrayHasKey('queue:failed', Artisan::all());
        self::assertArrayHasKey('queue:retry', Artisan::all());
        self::assertArrayHasKey('schedule:run', Artisan::all());
        self::assertArrayHasKey('operations:record-scheduler-heartbeat', Artisan::all());

        foreach ([
            'queue:work',
            'queue:restart',
            'queue:failed',
            'queue:retry',
            'schedule:run',
            'operations:record-scheduler-heartbeat',
        ] as $command) {
            self::assertStringContainsString($command, $runbook);
        }

        self::assertStringContainsString('operations.queue_heartbeat_stale_after', $runbook);
        self::assertStringContainsString('operations.scheduler_heartbeat_stale_after', $runbook);
        self::assertIsInt(config('operations.queue_heartbeat_stale_after'));
        self::assertIsInt(config('operations.scheduler_heartbeat_stale_after'));
        self::assertGreaterThan(0, config('operations.queue_heartbeat_stale_after'));
        self::assertGreaterThan(0, config('operations.scheduler_heartbeat_stale_after'));
    }
}
