<?php

return [
    'fast' => [
        'tries' => 1,
        'backoff' => [],
        'timeout' => 30,
    ],
    'external' => [
        'tries' => 3,
        'backoff' => [10, 60],
        'timeout' => 75,
    ],
    'batch' => [
        'tries' => 1,
        'backoff' => [],
        'timeout' => 75,
    ],
    'unique_for' => 300,
    'non_retryable_exceptions' => [
        InvalidArgumentException::class,
        LogicException::class,
    ],
];
