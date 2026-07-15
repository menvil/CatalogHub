<?php

return [
    'freshness' => [
        'fresh_hours' => (int) env('PRICE_FRESH_HOURS', 6),
        'stale_hours' => (int) env('PRICE_STALE_HOURS', 24),
        'expired_hours' => (int) env('PRICE_EXPIRED_HOURS', 48),
    ],

    // Widget endpoints are code-owned allowlist entries. Site settings may only
    // select a provider key and can never inject a URL, HTML, or script.
    'external_widgets' => [
        'providers' => [],
    ],
];
