<?php

return [
    'queue_heartbeat_stale_after' => (int) env('QUEUE_HEARTBEAT_STALE_AFTER', 300),
    'scheduler_heartbeat_stale_after' => (int) env('SCHEDULER_HEARTBEAT_STALE_AFTER', 300),
];
