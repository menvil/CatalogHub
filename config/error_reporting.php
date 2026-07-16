<?php

return [
    'enabled' => (bool) env('ERROR_REPORTING_ENABLED', false),
    'driver' => env('ERROR_REPORTING_DRIVER', 'log'),
    'dsn' => env('ERROR_REPORTING_DSN'),
    'environment' => env('ERROR_REPORTING_ENVIRONMENT', env('APP_ENV', 'production')),
    'sample_rate' => (float) env('ERROR_REPORTING_SAMPLE_RATE', 1.0),

    // Phase 21 does not authorize sending request/session PII to an external reporter.
    'send_default_pii' => false,
];
