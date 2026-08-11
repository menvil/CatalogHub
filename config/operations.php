<?php

return [
    'queue_heartbeat_stale_after' => max(1, (int) env('QUEUE_HEARTBEAT_STALE_AFTER', 300)),
    'scheduler_heartbeat_stale_after' => max(1, (int) env('SCHEDULER_HEARTBEAT_STALE_AFTER', 300)),
];
