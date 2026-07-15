<?php

return [
    'freshness' => [
        'fresh_hours' => (int) env('PRICE_FRESH_HOURS', 6),
        'stale_hours' => (int) env('PRICE_STALE_HOURS', 24),
        'expired_hours' => (int) env('PRICE_EXPIRED_HOURS', 48),
    ],
];
