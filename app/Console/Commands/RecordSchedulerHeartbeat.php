<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Operations\SchedulerHeartbeatService;
use Illuminate\Console\Command;

final class RecordSchedulerHeartbeat extends Command
{
    protected $signature = 'operations:record-scheduler-heartbeat';

    protected $description = 'Record the scheduler heartbeat for operational health checks';

    public function handle(SchedulerHeartbeatService $heartbeats): int
    {
        $heartbeats->record();

        $this->info('Scheduler heartbeat recorded.');

        return self::SUCCESS;
    }
}
